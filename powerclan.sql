-- PowerClan Database Schema
-- Version 2.0 (PHP 8.4)
-- MIT License

DROP TABLE IF EXISTS pc_config;
DROP TABLE IF EXISTS pc_members;
DROP TABLE IF EXISTS pc_news;
DROP TABLE IF EXISTS pc_wars;

CREATE TABLE pc_config (
   id int(11) NOT NULL auto_increment,
   clanname varchar(150) NOT NULL,
   clantag varchar(10) NOT NULL,
   url varchar(250) NOT NULL,
   serverpath varchar(250) NOT NULL,
   header varchar(200) NOT NULL,
   footer varchar(200) NOT NULL,
   tablebg1 varchar(7) NOT NULL,
   tablebg2 varchar(7) NOT NULL,
   tablebg3 varchar(7) NOT NULL,
   clrwon varchar(7) NOT NULL,
   clrdraw varchar(7) NOT NULL,
   clrlost varchar(7) NOT NULL,
   newslimit int(2) NOT NULL,
   warlimit int(2) NOT NULL,
   PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pc_members (
   id int(11) NOT NULL auto_increment,
   nick varchar(100) NOT NULL,
   email varchar(200) NOT NULL,
   password varchar(255) NOT NULL,
   work varchar(200) NOT NULL,
   realname varchar(250) NOT NULL,
   icq int(10) DEFAULT '0' NOT NULL,
   homepage varchar(250) NOT NULL,
   age int(2) DEFAULT '0' NOT NULL,
   hardware text NOT NULL,
   info text NOT NULL,
   pic varchar(250) NOT NULL,
   member_add enum('YES','NO') DEFAULT 'NO' NOT NULL,
   member_edit enum('YES','NO') DEFAULT 'NO' NOT NULL,
   member_del enum('YES','NO') DEFAULT 'NO' NOT NULL,
   news_add enum('YES','NO') DEFAULT 'NO' NOT NULL,
   news_edit enum('YES','NO') DEFAULT 'NO' NOT NULL,
   news_del enum('YES','NO') DEFAULT 'NO' NOT NULL,
   wars_add enum('YES','NO') DEFAULT 'NO' NOT NULL,
   wars_edit enum('YES','NO') DEFAULT 'NO' NOT NULL,
   wars_del enum('YES','NO') DEFAULT 'NO' NOT NULL,
   superadmin enum('YES','NO') DEFAULT 'NO' NOT NULL,
   PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE pc_news (
   id int(11) NOT NULL auto_increment,
   time int(14) DEFAULT '0' NOT NULL,
   userid int(11) NOT NULL,
   nick varchar(100) NOT NULL,
   email varchar(250) NOT NULL,
   title varchar(150) NOT NULL,
   text text NOT NULL,
   PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pc_wars (
   id int(11) NOT NULL auto_increment,
   enemy varchar(150) NOT NULL,
   enemy_tag varchar(10) NOT NULL,
   homepage varchar(250) NOT NULL,
   league varchar(150) NOT NULL,
   map1 varchar(100) NOT NULL,
   map2 varchar(100) NOT NULL,
   map3 varchar(100) NOT NULL,
   time int(14) DEFAULT '0' NOT NULL,
   report text NOT NULL,
   res1 varchar(50) NOT NULL,
   res2 varchar(50) NOT NULL,
   res3 varchar(50) NOT NULL,
   screen1 varchar(200) NOT NULL,
   screen2 varchar(200) NOT NULL,
   screen3 varchar(200) NOT NULL,
   PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default configuration
INSERT INTO pc_config (clanname, clantag, url, serverpath, header, footer, tablebg1, tablebg2, tablebg3, clrwon, clrdraw, clrlost, newslimit, warlimit)
VALUES('PowerClan', 'PC', 'https://www.powerscripts.org/', '', 'header.pc', 'footer.pc', '#A0A0A0', '#F0F0F0', '#E0E0E0', '#008000', '#808080', '#800000', '10', '10');

-- Default admin user (password: admin123 - CHANGE THIS!)
-- Password hash generated with password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO pc_members (nick, email, password, work, realname, icq, homepage, age, hardware, info, pic, member_add, member_edit, member_del, news_add, news_edit, news_del, wars_add, wars_edit, wars_del, superadmin)
VALUES('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Webmaster', '', 0, '', 0, '', '', '', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES');
