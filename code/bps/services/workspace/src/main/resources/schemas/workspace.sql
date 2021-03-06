-- ----------------------------------------------------------------------
-- SQL create script for BPS corpus info tables
-- ----------------------------------------------------------------------

-- Define the main workspace table
DROP TABLE IF EXISTS `workspace`;
CREATE TABLE `workspace` (
  `id`                INT(10) UNSIGNED PRIMARY KEY auto_increment NOT NULL,
  `name`              VARCHAR(255) NOT NULL default 'My Workspace',
  `description`       text NULL,
  `owner_id`          INT(10) UNSIGNED NOT NULL,
  `activeLifeStdDev`  DOUBLE NOT NULL default 0.0,
  `activeLifeWindow`  DOUBLE NOT NULL default 0.0,
  `generationOffset`  BIGINT NOT NULL default 0,
  `creation_time`     timestamp NOT NULL default '0000-00-00 00:00:00',
  `mod_time`          timestamp NOT NULL default CURRENT_TIMESTAMP
        on update CURRENT_TIMESTAMP,
	CONSTRAINT `wksp_ibfk_1` FOREIGN KEY (`owner_id`)
      REFERENCES `user` (`id`)
)ENGINE=MyIsam;
SHOW WARNINGS;

-- Define the workspace collapser rule table
-- We have a joint index on the id+name+item, and since we use Unicode, MySQL assumes
-- up to 3 bytes per char, and allows a total of 1000 bytes for a key. This is why
-- we tighten up the max lengths for the name and item (these are still pretty liberal).
-- 'item' field is (only) for matrix rules, and uses row-col name-pairs as the values
-- Note that this just holds the weights, and not the full definition.
DROP TABLE IF EXISTS `wksp_collapser_rule`;
CREATE TABLE `wksp_collapser_rule` (
  `wksp_id`       INT(10) UNSIGNED NOT NULL,
  `name`          VARCHAR(120) NOT NULL,
  `item`          VARCHAR(200) NOT NULL default '.',
  `weight`        DOUBLE NOT NULL default 1.0,
  `creation_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `mod_time`      timestamp NOT NULL default CURRENT_TIMESTAMP
        on update CURRENT_TIMESTAMP,
	PRIMARY KEY(`wksp_id`, `name`, `item`),
	CONSTRAINT `wcr_ibfk_3` FOREIGN KEY (`wksp_id`)
      REFERENCES `workspace` (`id`)
)ENGINE=MyIsam;
SHOW WARNINGS;

-- Define the configuration parameter table
DROP TABLE IF EXISTS `cfg_param`;
CREATE TABLE `cfg_param` (
  `id`            INT(10) UNSIGNED PRIMARY KEY auto_increment NOT NULL,
  `name`          VARCHAR(255) NOT NULL,
  `description`   text NULL,
  `scalar_type`   ENUM ('int', 'double') NOT NULL DEFAULT 'double',
  `int_default`   INT(10) not NULL default 0,
  `int_min`       INT(10) not NULL default -2147483648,
  `int_max`       INT(10) not NULL default 2147483647,
  `flt_default`   FLOAT(4,3) not NULL default 0.0,
  `flt_min`       FLOAT(4,3) not NULL default 0.0,
  `flt_max`       FLOAT(4,3) not NULL default 1.0,
  `creation_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `mod_time`      timestamp NOT NULL default CURRENT_TIMESTAMP
        on update CURRENT_TIMESTAMP
)ENGINE=MyIsam;
SHOW WARNINGS;

-- Define the workspace configuration parameter instance table
DROP TABLE IF EXISTS `wksp_cfg_param`;
CREATE TABLE `wksp_cfg_param` (
  `id`            INT(10) UNSIGNED PRIMARY KEY auto_increment NOT NULL,
  `cfgp_id`       INT(10) UNSIGNED NOT NULL,
  `wksp_id`       INT(10) UNSIGNED NOT NULL,
  `int_value`     INT(10) not NULL default 0,
  `flt_value`     FLOAT(4,3) not NULL default 0.0,
  `creation_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `mod_time`      timestamp NOT NULL default CURRENT_TIMESTAMP
        on update CURRENT_TIMESTAMP,
	CONSTRAINT `ucp_ibfk_1` FOREIGN KEY (`cfgp_id`)
      REFERENCES `cfg_param` (`id`),
	CONSTRAINT `ucp_ibfk_3` FOREIGN KEY (`wksp_id`)
      REFERENCES `workspace` (`id`)
)ENGINE=MyIsam;
SHOW WARNINGS;

-- Add Filter definition

-- Need to think abotu actions. Should ideally be a REST call
-- URI, payload, text description.
-- Could approximate this and interpret for now, but nice to model ideal.
-- CUD operations (READ not meaningful), individual
-- What does this mean, however, e.g., to map a nameref to an individual? CREATE/UPDATE? 
-- Need to define the resource model more clearly!!!

-----------------------  Clan Definition --------------------------

DROP TABLE IF EXISTS `clan`;
CREATE TABLE `clan` (
  `id`             INT(10) UNSIGNED PRIMARY KEY auto_increment NOT NULL,
  `workspace_id`      INT(10) UNSIGNED NOT NULL,
  `nrad_id`      INT(10) UNSIGNED NOT NULL,
  `creation_time`  timestamp NOT NULL default '0000-00-00 00:00:00',
  `mod_time`       timestamp NOT NULL default CURRENT_TIMESTAMP
        on update CURRENT_TIMESTAMP,
  CONSTRAINT `clan_wsfk_1` FOREIGN KEY (`workspace_id`)
      REFERENCES `workspace` (`id`),
  CONSTRAINT `clan_nrfk_2` FOREIGN KEY (`nrad_id`)
      REFERENCES `name_role_activity_doc` (`id`)
)ENGINE=MyIsam;
SHOW WARNINGS;

