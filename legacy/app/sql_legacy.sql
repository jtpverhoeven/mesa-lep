-- Adminer 5.4.2 MySQL 8.0.46 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

DROP TABLE IF EXISTS `applets`;
CREATE TABLE `applets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `applet` varchar(64) NOT NULL,
  `user` int NOT NULL,
  `appletStore` blob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `assayfields`;
CREATE TABLE `assayfields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `standard_value` varchar(64) NOT NULL,
  `position` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `assayprofiles`;
CREATE TABLE `assayprofiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `research_profile` int NOT NULL,
  `assay` int NOT NULL,
  `dillutions` text NOT NULL,
  `replicates` int NOT NULL DEFAULT '0',
  `reference` varchar(128) NOT NULL,
  `hidden` int NOT NULL DEFAULT '0',
  `project_order` int NOT NULL DEFAULT '1',
  `conf_trip` int NOT NULL DEFAULT '0',
  `reference_source` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `assays`;
CREATE TABLE `assays` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `name` varchar(128) NOT NULL,
  `type_base` int NOT NULL,
  `media_id` varchar(128) NOT NULL,
  `dillution` int NOT NULL,
  `replicates` int NOT NULL,
  `confirmation` int NOT NULL,
  `confirmation_type` int NOT NULL DEFAULT '1',
  `confirmation_script` text NOT NULL,
  `confirmation_support` text,
  `type` int NOT NULL,
  `meta_assays` text NOT NULL,
  `max_count` int NOT NULL,
  `min_count` int NOT NULL,
  `script` text NOT NULL,
  `custom_fields` text NOT NULL,
  `duration` varchar(5) NOT NULL,
  `start_from` varchar(128) NOT NULL,
  `active` int NOT NULL DEFAULT '1',
  `hide_report` int NOT NULL DEFAULT '0',
  `show_conf_table` text,
  `confirmation_init` int NOT NULL DEFAULT '0',
  `confirmation_depth` int DEFAULT '5',
  `uses_indicator` int NOT NULL DEFAULT '1',
  `uses_trip_indicator` int NOT NULL DEFAULT '1',
  `article_code` text NOT NULL,
  `billable` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `assaytypefields`;
CREATE TABLE `assaytypefields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_id` int NOT NULL,
  `name` varchar(32) NOT NULL,
  `alias` varchar(64) NOT NULL,
  `type` varchar(32) NOT NULL,
  `pos` int NOT NULL,
  `endresults_driver` int NOT NULL DEFAULT '1',
  `filter` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `assaytypes`;
CREATE TABLE `assaytypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `added_by` int NOT NULL,
  `added_date` varchar(32) NOT NULL,
  `active` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `assuranceforms`;
CREATE TABLE `assuranceforms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `data` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `is_complete` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_assuranceforms_date` (`date`),
  KEY `idx_assuranceforms_is_complete` (`is_complete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `bookmarks`;
CREATE TABLE `bookmarks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user` int NOT NULL,
  `type` int NOT NULL,
  `type_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `categories_clients`;
CREATE TABLE `categories_clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `clientcategory_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `changetracker`;
CREATE TABLE `changetracker` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `timestamp` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `assurance_form` int DEFAULT NULL,
  `project` int DEFAULT NULL,
  `sample` int DEFAULT NULL,
  `said` int DEFAULT NULL,
  `event` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `from` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `to` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `chats`;
CREATE TABLE `chats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sender` varchar(255) NOT NULL DEFAULT '',
  `receiver` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `sent` varchar(32) NOT NULL,
  `recd` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `to` (`receiver`),
  KEY `from` (`sender`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


SET NAMES utf8mb4;

DROP TABLE IF EXISTS `client_portal_assay`;
CREATE TABLE `client_portal_assay` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `portal_assay_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `clientcategories`;
CREATE TABLE `clientcategories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(5) DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  `title` varchar(12) DEFAULT NULL,
  `fname` varchar(128) DEFAULT NULL,
  `mname` varchar(128) DEFAULT NULL,
  `lname` varchar(128) DEFAULT NULL,
  `street_name` varchar(128) DEFAULT NULL,
  `street_number` varchar(6) DEFAULT NULL,
  `postal_code` varchar(12) DEFAULT NULL,
  `place` varchar(128) DEFAULT NULL,
  `country` varchar(128) DEFAULT NULL,
  `telephone` varchar(32) DEFAULT NULL,
  `cellphone` varchar(32) DEFAULT NULL,
  `email` varchar(512) DEFAULT NULL,
  `notes` text,
  `attachment` text,
  `active` int NOT NULL DEFAULT '1',
  `category` int DEFAULT NULL,
  `trip_red` int NOT NULL DEFAULT '1',
  `report_notes` text,
  `nvwa_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `debit_number` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `confirmations`;
CREATE TABLE `confirmations` (
  `said` int NOT NULL,
  `note` text,
  `in_use` text,
  `racetrack` text NOT NULL,
  `metadata` text NOT NULL,
  `isReady` int NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `id` (`id`),
  KEY `idx_confirmations_said` (`said`),
  KEY `idx_confirmations_id_said` (`id`,`said`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `confirmationtables`;
CREATE TABLE `confirmationtables` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `html` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `confkeystore`;
CREATE TABLE `confkeystore` (
  `innocdate` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `media` int NOT NULL,
  `param` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `value` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `contactgroupmembers`;
CREATE TABLE `contactgroupmembers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contactgroup_id` int NOT NULL,
  `email` text NOT NULL,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `contactgroups`;
CREATE TABLE `contactgroups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `client` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `cvars`;
CREATE TABLE `cvars` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cvar` varchar(128) NOT NULL,
  `value` varchar(1024) DEFAULT NULL,
  `default` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `docgenassociations`;
CREATE TABLE `docgenassociations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assay_id` int NOT NULL,
  `original_assay_id` int NOT NULL,
  `occurs_in` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `docgentemplates`;
CREATE TABLE `docgentemplates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `folder` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `active` int NOT NULL DEFAULT '1',
  `view_order` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `emailtemplates`;
CREATE TABLE `emailtemplates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `template` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `bcc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `defaultmail` int DEFAULT '0',
  `defaultmail_with_files` int DEFAULT '0',
  `defaultmail_for_files` int DEFAULT '0',
  `template_en` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `exports`;
CREATE TABLE `exports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction` int DEFAULT NULL,
  `client` int NOT NULL,
  `revision` int NOT NULL,
  `project` int NOT NULL,
  `print_version` int NOT NULL,
  `type` int NOT NULL,
  `naming_strategy` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT 'reference',
  `naming_description` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT '',
  `template` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `hash` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `date` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `report_reference` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `temporary` int NOT NULL DEFAULT '0',
  `was_authorized` int NOT NULL DEFAULT '0',
  `sample_id` int DEFAULT NULL,
  `sample_id_scope` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  PRIMARY KEY (`id`),
  KEY `project` (`project`),
  KEY `client` (`client`),
  KEY `client_project` (`client`,`project`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `fieldbindings`;
CREATE TABLE `fieldbindings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `analytical` int NOT NULL,
  `type` varchar(10) NOT NULL,
  `trigger_type` varchar(10) NOT NULL,
  `trigger_by` varchar(128) NOT NULL,
  `instructions` text NOT NULL,
  `exec_order` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `fieldconstants`;
CREATE TABLE `fieldconstants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `analysis` int NOT NULL,
  `const_name` varchar(32) NOT NULL,
  `const_type` int NOT NULL,
  `poll_col` varchar(128) NOT NULL,
  `const_value` varchar(128) NOT NULL,
  `operation` int NOT NULL,
  `restrictor` int NOT NULL,
  `restrictor_dil` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `filehistories`;
CREATE TABLE `filehistories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sample_id` int NOT NULL,
  `samplefile_id` int NOT NULL,
  `hash_name` text NOT NULL,
  `sent_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_by` int NOT NULL,
  `sent_to` text NOT NULL,
  `sent_method` text NOT NULL,
  `transaction_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sample_id` (`sample_id`),
  KEY `samplefile_id` (`samplefile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `footers`;
CREATE TABLE `footers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `lang` tinytext NOT NULL,
  `q` tinyint(1) DEFAULT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `groupprivileges`;
CREATE TABLE `groupprivileges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ruleId` int NOT NULL,
  `groupId` int NOT NULL,
  `scope` int NOT NULL,
  `controller` text NOT NULL,
  `action` text NOT NULL,
  `DOMName` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_groupprivileges_groupId` (`groupId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `groupusers`;
CREATE TABLE `groupusers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupId` int NOT NULL,
  `userId` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `keyrings`;
CREATE TABLE `keyrings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL,
  `type_id` int NOT NULL,
  `issued` int NOT NULL,
  `issued_to` int NOT NULL,
  `expires` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `labeldesigns`;
CREATE TABLE `labeldesigns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `zpl` text NOT NULL,
  `default_sample` int NOT NULL DEFAULT '0',
  `default_analysis` int NOT NULL DEFAULT '0',
  `default_printer` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `labelevents`;
CREATE TABLE `labelevents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event` varchar(1) NOT NULL,
  `event_group` int NOT NULL,
  `event_data` text NOT NULL,
  `print_label` int NOT NULL,
  `print_to` int NOT NULL,
  `copies` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `labjournals`;
CREATE TABLE `labjournals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user` int NOT NULL,
  `page_date` int NOT NULL,
  `contents` longblob NOT NULL,
  `private` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `labtalkevents`;
CREATE TABLE `labtalkevents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user` int NOT NULL,
  `receiver` int NOT NULL,
  `time` int NOT NULL,
  `context` int NOT NULL,
  `automation_context` int NOT NULL,
  `reply_to` int NOT NULL,
  `payload` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `matrix`;
CREATE TABLE `matrix` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `icon` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `matrixcontent`;
CREATE TABLE `matrixcontent` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assay_base` int NOT NULL,
  `matrix` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `short_name` varchar(32) DEFAULT NULL,
  `confirmation_media` int NOT NULL DEFAULT '0',
  `type` int NOT NULL DEFAULT '1',
  `supplements` text,
  `hasDate` int NOT NULL DEFAULT '1',
  `confirmation_controls` varchar(128) DEFAULT NULL,
  `active` int NOT NULL DEFAULT '1',
  `used_for_prediction` int NOT NULL DEFAULT '1',
  `prediction_qom` int NOT NULL DEFAULT '1',
  `prediction_default_quant` int NOT NULL DEFAULT '18',
  `acceptable_range` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `metadata`;
CREATE TABLE `metadata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sample` int NOT NULL,
  `name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `meta_data_key_id` int DEFAULT NULL,
  `meta_order` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `portal`;
CREATE TABLE `portal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pk` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `pv` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `portalassaycontent`;
CREATE TABLE `portalassaycontent` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assay_id` int NOT NULL,
  `common_id` int NOT NULL,
  `original_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `portalassays`;
CREATE TABLE `portalassays` (
  `id` int NOT NULL AUTO_INCREMENT,
  `common_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `common_name_en` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `alertable` int DEFAULT '1',
  `active` int NOT NULL DEFAULT '1',
  `selectable` int NOT NULL DEFAULT '1',
  `border_reaction` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `printers`;
CREATE TABLE `printers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `adres` varchar(45) NOT NULL,
  `port` int NOT NULL,
  `type` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `printing`;
CREATE TABLE `printing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `profile` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `productgroups`;
CREATE TABLE `productgroups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `portal_id` int NOT NULL,
  `name` text NOT NULL,
  `default` int NOT NULL DEFAULT '0',
  `visible` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `profiles`;
CREATE TABLE `profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `title` varchar(32) NOT NULL,
  `first_name` varchar(128) NOT NULL,
  `last_name` varchar(128) NOT NULL,
  `sex` varchar(1) NOT NULL,
  `function` varchar(256) NOT NULL,
  `email` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `avatar_uri` varchar(256) NOT NULL,
  `dashboard` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `projectfields`;
CREATE TABLE `projectfields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `alias` varchar(64) NOT NULL,
  `type` varchar(10) NOT NULL,
  `std_value` text NOT NULL,
  `position` int NOT NULL,
  `keep_current` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `projectnotes`;
CREATE TABLE `projectnotes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(32) DEFAULT NULL,
  `client` int NOT NULL,
  `subclient` int NOT NULL,
  `project_name` varchar(128) DEFAULT NULL,
  `project_notes` text,
  `project_date` varchar(32) DEFAULT NULL,
  `last_edit` int DEFAULT NULL,
  `custom_fields` text,
  `revision` int NOT NULL DEFAULT '1',
  `auth_status` int NOT NULL DEFAULT '0',
  `auth_by` int DEFAULT NULL,
  `auth_on` int DEFAULT NULL,
  `print_version` int NOT NULL DEFAULT '0',
  `predicted_end` int DEFAULT NULL,
  `project_extra` text,
  `is_ready` int NOT NULL DEFAULT '0',
  `special_type` int NOT NULL DEFAULT '0',
  `rap_stat` int NOT NULL DEFAULT '0',
  `rap_by` int DEFAULT NULL,
  `rap_on` varchar(32) DEFAULT NULL,
  `rap_rev` int DEFAULT NULL,
  `became_ready_on` varchar(32) DEFAULT NULL,
  `started` int NOT NULL DEFAULT '0',
  `added_by` int DEFAULT NULL,
  `portal_id` int DEFAULT NULL,
  `locked` int DEFAULT '0',
  `locked_by` int DEFAULT NULL,
  `lock_pass` varchar(255) DEFAULT NULL,
  `lock_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `print_info` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `referencesources`;
CREATE TABLE `referencesources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `client` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `researchprofiles`;
CREATE TABLE `researchprofiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `name` varchar(128) NOT NULL,
  `global` int NOT NULL,
  `client` int NOT NULL,
  `active` int NOT NULL DEFAULT '1',
  `portal_visible` int NOT NULL DEFAULT '0',
  `lims_visible` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `results`;
CREATE TABLE `results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sample` int NOT NULL,
  `sa_id` int NOT NULL,
  `follow_no` int NOT NULL,
  `profile` int NOT NULL,
  `assay` int NOT NULL,
  `assay_base` int NOT NULL,
  `roaming_id` int NOT NULL,
  `df` varchar(32) NOT NULL,
  `rep` int NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_results_sample` (`sample`),
  KEY `idx_results_sa_id` (`sa_id`),
  KEY `idx_results_follow_no_sample` (`follow_no`,`sample`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `revisions`;
CREATE TABLE `revisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user` int NOT NULL,
  `time` int NOT NULL,
  `scope` varchar(64) NOT NULL,
  `scopeId` int NOT NULL,
  `sa_id` int NOT NULL,
  `result_id` int NOT NULL,
  `changed` text NOT NULL,
  `from` text NOT NULL,
  `to` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `roaminganalysis`;
CREATE TABLE `roaminganalysis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `said` int NOT NULL,
  `assay` int NOT NULL,
  `dillutions` text NOT NULL,
  `replicates` int NOT NULL DEFAULT '0',
  `reference` varchar(32) NOT NULL,
  `reference_scope` varchar(5) NOT NULL,
  `reference_source` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `sampleanalysis`;
CREATE TABLE `sampleanalysis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `profile_group` int NOT NULL,
  `sample` int NOT NULL,
  `follow_number` int NOT NULL,
  `profile` int NOT NULL,
  `assay` int NOT NULL,
  `assay_base` int NOT NULL,
  `roaming_id` int DEFAULT NULL,
  `predicted_end` int NOT NULL,
  `original_assay_base` int DEFAULT NULL,
  `conf_requested` int NOT NULL DEFAULT '0',
  `is_ready` int NOT NULL DEFAULT '0',
  `project` int DEFAULT NULL,
  `project_order` int NOT NULL DEFAULT '1',
  `storedResult` text,
  PRIMARY KEY (`id`),
  KEY `idx_sampleanalysis_sample` (`sample`),
  KEY `idx_sampleanalysis_project` (`project`),
  KEY `idx_sampleanalysis_profile_group` (`profile_group`),
  KEY `idx_sampleanalysis_sample_follow_number` (`sample`,`follow_number`),
  KEY `idx_sampleanalysis_assay_base` (`assay_base`),
  KEY `idx_sampleanalysis_original_assay_base` (`original_assay_base`),
  KEY `idx_sampleanalysis_assay_base_original_assay_base` (`assay_base`,`original_assay_base`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `samplebuffers`;
CREATE TABLE `samplebuffers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client` int NOT NULL,
  `source` int NOT NULL DEFAULT '1',
  `project` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `portal_follow_no` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT '',
  `sampling_date` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `sampling_method` int NOT NULL,
  `sample_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `sample_details` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `tht` int NOT NULL,
  `tht_date` date DEFAULT NULL,
  `meta` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `analyses_selected` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `misc_directions` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `authorized` int NOT NULL DEFAULT '0',
  `project_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `portal_order_info` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `portal_analyses` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `portal_meta` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `portal_notes` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `portal_id` int DEFAULT NULL,
  `portal_product_group_id` int DEFAULT NULL,
  `portal_project` int DEFAULT NULL,
  `receive_time` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `receive_date` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `tht_code` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `date_registered` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `sample_research_type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `sample_properties` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `samplefields`;
CREATE TABLE `samplefields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `alias` varchar(64) NOT NULL,
  `type` varchar(10) NOT NULL,
  `std_value` text NOT NULL,
  `position` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `samplefiles`;
CREATE TABLE `samplefiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sample_id` int NOT NULL,
  `hash_name` text NOT NULL,
  `original_name` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `visible_for_client` int NOT NULL DEFAULT '1',
  `client_id` int NOT NULL,
  `sent_to_client` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sampleprocedurefields`;
CREATE TABLE `sampleprocedurefields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `alias` varchar(128) NOT NULL,
  `position` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `sampleprocedures`;
CREATE TABLE `sampleprocedures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `active` int NOT NULL DEFAULT '1',
  `hide` int NOT NULL DEFAULT '0',
  `fields` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `samples`;
CREATE TABLE `samples` (
  `id` int NOT NULL AUTO_INCREMENT,
  `barcode` varchar(32) NOT NULL,
  `tht_code` varchar(32) DEFAULT NULL,
  `follow_no` int NOT NULL,
  `description` text NOT NULL,
  `client_description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `sampling_method` int NOT NULL,
  `date_registered` varchar(32) NOT NULL,
  `registered_by` int NOT NULL,
  `client` int NOT NULL,
  `subclient` int NOT NULL,
  `project` int NOT NULL,
  `custom_fields` text NOT NULL,
  `predicted_end` int NOT NULL,
  `sample_innoculated` varchar(64) NOT NULL,
  `stored_in` varchar(5) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `diluted_at` varchar(5) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `sample_note` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `sample_type` varchar(1) NOT NULL DEFAULT 'S',
  `leg_type` varchar(1) DEFAULT '-',
  `sample_extra` text,
  `isEmpty` int NOT NULL DEFAULT '1',
  `source` int NOT NULL DEFAULT '0',
  `analyses_data` text NOT NULL,
  `portal_analyses` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `portal_sample_id` int DEFAULT NULL,
  `portal_product_group_id` int DEFAULT NULL,
  `portal_project_id` int DEFAULT NULL,
  `portal_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  PRIMARY KEY (`id`),
  KEY `idx_samples_project` (`project`),
  KEY `idx_samples_sample_innoculated` (`sample_innoculated`),
  KEY `idx_samples_isEmpty` (`isEmpty`),
  KEY `idx_samples_client` (`client`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `session_id` varchar(100) NOT NULL DEFAULT '',
  `session_data` text NOT NULL,
  `expires` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `subclients`;
CREATE TABLE `subclients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client` int DEFAULT NULL,
  `loc_name` varchar(128) DEFAULT NULL,
  `loc_adres` varchar(128) DEFAULT NULL,
  `loc_postal` varchar(12) DEFAULT NULL,
  `loc_place` varchar(128) DEFAULT NULL,
  `loc_country` varchar(32) DEFAULT NULL,
  `loc_phone` varchar(32) DEFAULT NULL,
  `loc_fax` varchar(32) DEFAULT NULL,
  `loc_email` varchar(128) DEFAULT NULL,
  `loc_raploc` varchar(128) DEFAULT NULL,
  `loc_raptitle` varchar(128) DEFAULT NULL,
  `loc_rapfun` varchar(128) DEFAULT NULL,
  `loc_rapfname` varchar(128) DEFAULT NULL,
  `loc_raplname` varchar(128) DEFAULT NULL,
  `loc_rapadres` varchar(128) DEFAULT NULL,
  `loc_rappostal` varchar(32) DEFAULT NULL,
  `loc_rapplace` varchar(128) DEFAULT NULL,
  `loc_rapcountry` varchar(64) DEFAULT NULL,
  `aa_id` int NOT NULL,
  `last_edit` varchar(32) NOT NULL,
  UNIQUE KEY `id_2` (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `testactions`;
CREATE TABLE `testactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_id` int DEFAULT NULL,
  `action` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `order` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `tests`;
CREATE TABLE `tests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `testset_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `testsets`;
CREATE TABLE `testsets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `usergroups`;
CREATE TABLE `usergroups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupName` varchar(64) NOT NULL,
  `groupLeader` int NOT NULL,
  `superGroup` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enabled` int NOT NULL DEFAULT '1',
  `username` varchar(64) NOT NULL,
  `salt1` varchar(32) NOT NULL,
  `salt2` varchar(32) NOT NULL,
  `pattern` varchar(64) NOT NULL,
  `password` varchar(128) NOT NULL,
  `sessionStarted` varchar(32) NOT NULL,
  `lastPing` varchar(32) NOT NULL,
  `sessionToken` varchar(256) NOT NULL,
  `sessionIp` varchar(64) NOT NULL,
  `sessionAgent` varchar(256) NOT NULL,
  `bruteCounter` int NOT NULL,
  `bruteLock` int NOT NULL,
  `lang` varchar(3) NOT NULL,
  `alias` varchar(32) DEFAULT NULL,
  `last_seen` varchar(45) DEFAULT NULL,
  `printer` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `vetoresults`;
CREATE TABLE `vetoresults` (
  `id` int NOT NULL AUTO_INCREMENT,
  `said` int NOT NULL,
  `parameter` varchar(512) NOT NULL,
  `result` varchar(512) NOT NULL,
  `reason` varchar(512) NOT NULL,
  `disposition` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `worklists`;
CREATE TABLE `worklists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `analyses` text NOT NULL,
  `no_columns` int NOT NULL DEFAULT '1',
  `title` varchar(128) DEFAULT NULL,
  `subtitle` varchar(128) DEFAULT NULL,
  `footer_text` varchar(128) DEFAULT NULL,
  `columns` text,
  `colum_groups` text,
  `extra_rows` text,
  `extra_pages` text,
  `orientation` varchar(1) NOT NULL DEFAULT 'P',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- 2026-09-07 16:53:50 UTC