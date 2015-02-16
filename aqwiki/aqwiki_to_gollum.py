#!/usr/bin/python

import ConfigParser
import argparse

import os

from git import Repo, Actor, Commit
import git.exc as GitExceptions

import cymysql as mysql

import dsnparse

from time import (time, altzone)
from cStringIO import StringIO
from gitdb import IStream
import sys
import re
parser = argparse.ArgumentParser(description='Import AqWiki into Gollum')

parser.add_argument('aqwiki_name', type=str,  help='AqWiki name')
parser.add_argument('aqwiki_dir', type=str,  help='AqWiki Directory')
parser.add_argument('gollum_dir', type=str,  help='Gollum Repository')

args = parser.parse_args()
#print(args)

config_filename = "%s/etc/aqwiki.ini" % args.aqwiki_dir

def getConfig(config_filename):
	if not os.path.exists(config_filename):
		print "Cannot import config file"
		sys.exit(5)


	# Parsing ini files is hard 1/2: Ini files have quoted values
	class MyConfigParser(ConfigParser.RawConfigParser):
	    def get(self, section, option):
	        val = ConfigParser.RawConfigParser.get(self, section, option)
	        return val.lstrip('"').rstrip('"')

	# Parsing ini files is hard 2/2: Ini files don't *need* a section header
	class FakeSecHead(object):
		def __init__(self, fp):
		  self.fp = fp
		  self.sechead = '[data]\n'
		def readline(self):
		  if self.sechead:
		    try: return self.sechead
		    finally: self.sechead = None
		  else: return self.fp.readline()

	config = MyConfigParser()
	config.readfp(FakeSecHead(open(config_filename)))
	return config


############## Get config details

config = getConfig(config_filename)

try:
	repo = Repo(args.gollum_dir)
except GitExceptions.InvalidGitRepositoryError:
	print "Error: Gollum directory isn't a git repo"
	sys.exit(5)

class AqWiki:

	def __init__(self, dsn):
		############## Connect to AqWiki

		db_config = dsnparse.parse(dsn)

		self.dbconnection = mysql.connect(
		    user=db_config.username,
		    passwd=db_config.password,
		    db=db_config.paths[0],
		    charset='utf8')


	def list_pages(self, wikiname):
		############## Get list of pages
		cursor = self.dbconnection.cursor(mysql.cursors.DictCursor)
		cursor.execute(
		    "select * from wikipage where wiki = %s",
		    (wikiname))
		pages = {}
		pagelist = cursor.fetchall()
		for page in pagelist:
			pages[page['name']] = page
		return pages

	def list_users(self):
		############## Get list of pages
		cursor = self.dbconnection.cursor(mysql.cursors.DictCursor)
		cursor.execute("select * from users")
		users = {}
		userlist = cursor.fetchall()
		for user in userlist:
			users[user['username']] = user
		return users

	def get_revisions(self, page):
		############## Get list of pages
		cursor = self.dbconnection.cursor(mysql.cursors.DictCursor)
		print page
		cursor.execute(
		    "select * from revision where page = %s order by created",
		    (int(page['page'])))

		return cursor.fetchall()

	def list_revisions(self, wikiname):
		############## Get list of pages
		cursor = self.dbconnection.cursor(mysql.cursors.DictCursor)
		cursor.execute(
		    "select revision.*, wikipage.name as pagename from revision, wikipage where wikipage.wiki = %s and wikipage.page = revision.page order by revision.created",
		    (wikiname))

		return cursor.fetchall()

	def convert_content(self, content):
		content = re.sub(r"\(\((.*?)\)\)", r'[[\1]]', content)
		return content


aqwiki = AqWiki(dsn=config.get("data", "db"))

pages = aqwiki.list_pages(args.aqwiki_name)
users = aqwiki.list_users()
revs  = aqwiki.list_revisions(args.aqwiki_name)

ignore = ('contents', 'recent', 'help', 'search', 'Playground')
#only = ("frontPage",)

move = {
	'frontPage': 'home'
}

only = ()

conf_encoding = 'UTF-8'
tree = repo.index.write_tree()

repo.index.commit("Aqwiki Import Starts")

def get_binsha(new_commit):
	stream = StringIO()
	new_commit._serialize(stream)
	streamlen = stream.tell()
	stream.seek(0)

	istream = repo.odb.store(IStream(Commit.type, streamlen, stream))

	return  istream.binsha

def dashitall(name):
    #s1 = re.sub('_', r'-', name)
    s1 = re.sub('(.)([A-Z][a-z]+)', r'\1-\2', name)
    return re.sub('([a-z0-9])([A-Z])', r'\1-\2', s1).lower()

for rev in revs:
	pagename = rev['pagename']

	if pagename in ignore:
		continue
	if only and pagename not in only:
		continue

	if pagename in move:
		pagename = move[pagename]

	print pagename, ' - ', rev['comment']
	new_file_path = os.path.join(repo.working_tree_dir, dashitall(pagename)+".textile")
	fp = open(new_file_path, 'wb')
	rev['content'] = aqwiki.convert_content(rev['content'])
	fp.write(rev['content'].encode('utf8'))
	fp.close()
	try: 
		actor = Actor(rev['creator'], users[rev['creator']]['email'])
	except KeyError:
		actor = Actor(rev['creator'], "%s@localhost" % rev['creator'])

	# action_date = rev['created'].strftime('%s')
	action_date = str(rev['created'])
	parents = [ repo.head.commit ]
	message = "%s: %s" % (pagename, rev['comment'])

	os.environ["GIT_AUTHOR_DATE"] = action_date
	os.environ["GIT_COMMITTER_DATE"] = action_date

	repo.index.add([new_file_path])
	repo.index.commit(message, author=actor, committer=actor)