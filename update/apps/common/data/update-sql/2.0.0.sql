--
-- Update sql for MailWizz EMA from version 1.8.7 to 2.0.0
--

UPDATE `survey_field_type` SET `class_alias` = REPLACE(`class_alias`, 'FieldBuilder', 'SurveyFieldBuilder') WHERE 1;
UPDATE `list_field_type` SET `class_alias` = REPLACE(`class_alias`, 'FieldBuilder', 'ListFieldBuilder') WHERE 1;
UPDATE `list_field_type` SET `class_alias` = REPLACE(`class_alias`, 'field-builder', 'list-field-builder') WHERE 1;

-- --------------------------------------------------------

--
-- Table structure for table `translation_message`
--

DROP TABLE IF EXISTS `translation_message`;
CREATE TABLE `translation_message` (
   `id` int(11) NOT NULL,
   `language` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
   `translation` text COLLATE utf8mb4_unicode_ci,
   PRIMARY KEY (`id`, `language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `translation_source_message`
--

DROP TABLE IF EXISTS `translation_source_message`;
CREATE TABLE `translation_source_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Constraints for table `translation_message`
--
ALTER TABLE `translation_message`
    ADD CONSTRAINT `fk_message_source_message` FOREIGN KEY (`id`) REFERENCES `translation_source_message` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

--
-- Table structure for table `queue_monitor`
--

DROP TABLE IF EXISTS `queue_monitor`;
CREATE TABLE `queue_monitor` (
  `id` char(36) NOT NULL,
  `message_id` char(36) DEFAULT NULL,
  `queue` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_queue_monitor_user1_idx` (`user_id`),
  KEY `fk_queue_monitor_customer1_idx` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Constraints for table `queue_monitor`
--
ALTER TABLE `queue_monitor`
  ADD CONSTRAINT `fk_queue_monitor_customer1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_queue_monitor_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE NO ACTION;
  
--
-- Add support for emojis in the most important places
--

ALTER TABLE `article` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `article_category` CHANGE `description` `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
ALTER TABLE `campaign_extra_tag` CHANGE `content` `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `campaign_forward_friend` CHANGE `message` `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `campaign_random_content` CHANGE `content` `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `campaign_template` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL, CHANGE `plain_text` `plain_text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
ALTER TABLE `common_email_template` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `customer_action_log` CHANGE `message` `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `customer_campaign_tag` CHANGE `content` `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `customer_email_template` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `customer_message` CHANGE `message` `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `list_page` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `list_page_type` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `page` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `price_plan` CHANGE `description` `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `start_page` CHANGE `content` `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
ALTER TABLE `survey` CHANGE `description` `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
ALTER TABLE `user_message` CHANGE `message` `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_send_group`
--

DROP TABLE IF EXISTS `campaign_send_group`;
CREATE TABLE IF NOT EXISTS `campaign_send_group` (
    `group_id` int(11) NOT NULL AUTO_INCREMENT,
    `group_uid` char(13) NOT NULL,
    `customer_id` int(11) NOT NULL,
    `name` varchar(190) NOT NULL,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`group_id`),
    UNIQUE KEY `group_uid` (`group_uid`),
    UNIQUE KEY `customer_id_name` (`customer_id`, `name`),
    KEY `fk_campaign_send_group_customer1_idx` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Constraints for table `campaign_send_group`
--
ALTER TABLE `campaign_send_group`
    ADD CONSTRAINT `fk_campaign_send_group_customer1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Alter the campaign table to add the index
--
ALTER TABLE `campaign` ADD `send_group_id` int(11) DEFAULT NULL AFTER `group_id`; 
ALTER TABLE `campaign` ADD KEY `fk_campaign_campaign_send_group1_idx` (`send_group_id`);
ALTER TABLE `campaign` ADD CONSTRAINT `fk_campaign_campaign_send_group1` FOREIGN KEY (`send_group_id`) REFERENCES `campaign_send_group` (`group_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Drop the `campaign_temporary_source` table 
--
DROP TABLE `campaign_temporary_source`;

--
-- Drop the `merged` column from the `list` table 
--
ALTER TABLE `list` DROP `merged`;

--
-- Alter the customer table
--
ALTER TABLE `customer` ADD `parent_id` int(11) DEFAULT NULL AFTER `customer_uid`; 
ALTER TABLE `customer` ADD KEY `fk_customer_parent1_idx` (`parent_id`);
ALTER TABLE `customer` ADD CONSTRAINT `fk_customer_parent1` FOREIGN KEY (`parent_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Alter the api_key table
-- 
ALTER TABLE `customer_api_key` DROP KEY `public_UNIQUE`;
ALTER TABLE `customer_api_key` CHANGE `public` `key` CHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `customer_api_key` ADD UNIQUE KEY `key_UNIQUE` (`key`);
ALTER TABLE `customer_api_key` DROP `private`;