-- MySQL dump 8.21
--
-- Host: localhost    Database: aqwiki
---------------------------------------------------------
-- Server version	3.23.49-log

--
-- Table structure for table 'revision'
--

CREATE TABLE revision (
  revision int(10) unsigned NOT NULL auto_increment,
  page int(10) NOT NULL default '0',
  content mediumtext NOT NULL,
  comment tinytext,
  creator tinytext NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (revision)
) TYPE=MyISAM;

--
-- Table structure for table 'users'
--

CREATE TABLE users (
  id int(10) unsigned NOT NULL auto_increment,
  username varchar(64) NOT NULL default '',
  real_name tinytext,
  email tinytext,
  birthday date default NULL,
  password tinytext,
  location int(11) default NULL,
  last_access timestamp(14) NOT NULL,
  date_creation timestamp(14) NOT NULL,
  access_level int(11) default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

--
-- Table structure for table 'wikipage'
--

CREATE TABLE wikipage (
  page int(10) unsigned NOT NULL auto_increment,
  wiki tinytext NOT NULL,
  name tinytext NOT NULL,
  spinlock bigint(20) default NULL,
  created datetime default NULL,
  modified timestamp(14) NOT NULL,
  origin tinytext,
  yalelock tinytext,
  PRIMARY KEY  (page)
) TYPE=MyISAM;

