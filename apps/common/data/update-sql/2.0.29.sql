--
-- Update sql for MailWizz EMA from version 2.0.28 to 2.0.29
--

--
-- Table structure for table `domain_blacklist`
--

DROP TABLE IF EXISTS `domain_blacklist`;
CREATE TABLE IF NOT EXISTS `domain_blacklist` (
    `domain_id` int(11) NOT NULL AUTO_INCREMENT,
    `domain` varchar(100) NOT NULL,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`domain_id`),
    UNIQUE KEY `domain_UNIQUE` (`domain`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------


--
-- Table structure for table `campaign_abtest`
--

DROP TABLE IF EXISTS `campaign_abtest`;
CREATE TABLE IF NOT EXISTS `campaign_abtest` (
    `test_id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `winner_criteria_opens_count` int(11) DEFAULT NULL,
    `winner_criteria_days_count` int(11) DEFAULT NULL,
    `winner_criteria_days_start_date` datetime DEFAULT NULL,
    `winner_criteria_operator` enum('or', 'and') DEFAULT 'or',
    `winner_opens_count_reached_at` DATETIME NULL DEFAULT NULL,
    `winner_days_count_reached_at` DATETIME NULL DEFAULT NULL,
    `winner_decided_by_opens_count` enum('no','yes') NOT NULL DEFAULT 'no',
    `winner_decided_by_days_count` enum('no','yes') NOT NULL DEFAULT 'no',
    `enabled` enum('no','yes') NOT NULL DEFAULT 'no',
    `status` char(15) NOT NULL DEFAULT 'inactive',
    `completed_at` DATETIME NULL DEFAULT NULL,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`test_id`),
    KEY `fk_campaign_abtest_campaign1_idx` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Constraints for table `campaign_abtest`
--

ALTER TABLE `campaign_abtest`
    ADD CONSTRAINT `fk_campaign_abtest_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Table structure for table `campaign_abtest_subject`
--

DROP TABLE IF EXISTS `campaign_abtest_subject`;
CREATE TABLE IF NOT EXISTS `campaign_abtest_subject` (
    `subject_id` int(11) NOT NULL AUTO_INCREMENT,
    `test_id` int(11) NOT NULL,
    `subject` varchar(500) NOT NULL,
    `is_winner` enum('no','yes') NOT NULL DEFAULT 'no',
    `opens_count` int(11) NOT NULL DEFAULT 0,
    `usage_count` int(11) NOT NULL DEFAULT 0,
    `status` char(15) NOT NULL DEFAULT 'active',
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`subject_id`),
    KEY `fk_campaign_abtest_subject_campaign_abtest1_idx` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Constraints for table `campaign_abtest_subject`
--

ALTER TABLE `campaign_abtest_subject`
    ADD CONSTRAINT `fk_campaign_abtest_subject_campaign_abtest1` FOREIGN KEY (`test_id`) REFERENCES `campaign_abtest` (`test_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Table structure for table `campaign_abtest_subject_to_delivery_log`
--

DROP TABLE IF EXISTS `campaign_abtest_subject_to_delivery_log`;
CREATE TABLE IF NOT EXISTS `campaign_abtest_subject_to_delivery_log` (
    `subject_id` int(11) NOT NULL,
    `log_id` bigint(20) NOT NULL,
    PRIMARY KEY (`subject_id`, `log_id`),
    KEY `fk_campaign_abtest_subject_to_delivery_log_subject1_idx` (`subject_id`),
    KEY `fk_campaign_abtest_subject_to_delivery_log_log1_idx` (`log_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Constraints for table `campaign_abtest_subject_to_delivery_log`
--

ALTER TABLE `campaign_abtest_subject_to_delivery_log`
    ADD CONSTRAINT `fk_campaign_abtest_subject_to_delivery_log_subject1` FOREIGN KEY (`subject_id`) REFERENCES `campaign_abtest_subject` (`subject_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
    ADD CONSTRAINT `fk_campaign_abtest_subject_to_delivery_log_log1` FOREIGN KEY (`log_id`) REFERENCES `campaign_delivery_log` (`log_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Table structure for table `campaign_abtest_subject_to_track_open`
--

DROP TABLE IF EXISTS `campaign_abtest_subject_to_track_open`;
CREATE TABLE IF NOT EXISTS `campaign_abtest_subject_to_track_open` (
    `subject_id` int(11) NOT NULL,
    `open_id` bigint(20) NOT NULL,
    PRIMARY KEY (`subject_id`, `open_id`),
    KEY `fk_campaign_abtest_subject_to_track_open_subject1_idx` (`subject_id`),
    KEY `fk_campaign_abtest_subject_to_track_open_open1_idx` (`open_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Constraints for table `campaign_abtest_subject_to_track_open`
--

ALTER TABLE `campaign_abtest_subject_to_track_open`
    ADD CONSTRAINT `fk_campaign_abtest_subject_to_track_open_subject1` FOREIGN KEY (`subject_id`) REFERENCES `campaign_abtest_subject` (`subject_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
    ADD CONSTRAINT `fk_campaign_abtest_subject_to_track_open_open1` FOREIGN KEY (`open_id`) REFERENCES `campaign_track_open` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;


--
-- Add the bounce server name column
--
ALTER TABLE `bounce_server` ADD `name` VARCHAR(255) NULL AFTER `customer_id`;

--
-- Add the feedback loop server name column
--
ALTER TABLE `feedback_loop_server` ADD `name` VARCHAR(255) NULL AFTER `customer_id`;


--
-- Add the email box monitor name column
--
ALTER TABLE `email_box_monitor` ADD `name` VARCHAR(255) NULL AFTER `customer_id`;
