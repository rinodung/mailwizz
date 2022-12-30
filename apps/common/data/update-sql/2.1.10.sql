--
-- Update sql for MailWizz EMA from version 2.1.9 to 2.1.10
--

--
-- Table structure for table `delivery_server_warmup_plan`
--

DROP TABLE IF EXISTS `delivery_server_warmup_plan`;
CREATE TABLE IF NOT EXISTS `delivery_server_warmup_plan` (
    `plan_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NULL DEFAULT NULL,
    `name` varchar(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `sending_limit` INT(11) NOT NULL DEFAULT '0',
    `sendings_count` INT(11) NOT NULL DEFAULT '0',
    `sending_quota_type` enum('hourly','daily','monthly') NOT NULL DEFAULT 'hourly',
    `sending_increment_ratio` INT(11) NOT NULL DEFAULT '0',
    `sending_strategy` enum('exponential','linear') NOT NULL DEFAULT 'linear',
    `sending_limit_type` enum('total','targeted') NOT NULL DEFAULT 'total',
    `status` char(15) NOT NULL DEFAULT 'draft',
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`plan_id`),
    KEY `fk_ds_warmup_plan_customer1_idx` (`customer_id`)
) ENGINE=InnoDB  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_server_warmup_plan_schedule`
--

DROP TABLE IF EXISTS `delivery_server_warmup_plan_schedule`;
CREATE TABLE IF NOT EXISTS `delivery_server_warmup_plan_schedule` (
    `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
    `plan_id` int(11) NOT NULL,
    `quota` INT NOT NULL DEFAULT '0',
    `increment` INT NOT NULL DEFAULT '0',
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`schedule_id`),
    KEY `fk_ds_warmup_plan_schedule_ds_warmup_plan1_idx` (`plan_id`)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_server_warmup_plan_schedule_log`
--

DROP TABLE IF EXISTS `delivery_server_warmup_plan_schedule_log`;
CREATE TABLE IF NOT EXISTS `delivery_server_warmup_plan_schedule_log` (
    `server_id` int(11) NOT NULL,
    `schedule_id` int(11) NOT NULL,
    `plan_id` int(11) NOT NULL,
    `allowed_quota` INT NOT NULL DEFAULT '0',
    `used_quota` INT NOT NULL DEFAULT '0',
    `started_at` datetime NULL,
    `status` char(15) NOT NULL DEFAULT 'processing',
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`server_id`, `schedule_id`),
    KEY `fk_ds_warmup_plan_schedule_log_delivery_server1_idx` (`server_id`),
    KEY `fk_ds_warmup_plan_schedule_log_ds_warmup_plan_schedule1_idx` (`schedule_id`),
    KEY `fk_ds_warmup_plan_schedule_log_ds_warmup_plan1_idx` (`plan_id`)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Constraints for table `delivery_server_warmup_plan`
--
ALTER TABLE `delivery_server_warmup_plan`
    ADD CONSTRAINT `fk_ds_warmup_plan_customer1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `delivery_server_warmup_plan_schedule`
--
ALTER TABLE `delivery_server_warmup_plan_schedule`
    ADD CONSTRAINT `fk_ds_warmup_plan_schedule_ds_warmup_plan1` FOREIGN KEY (`plan_id`) REFERENCES `delivery_server_warmup_plan` (`plan_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `delivery_server_warmup_plan_schedule_log`
--
ALTER TABLE `delivery_server_warmup_plan_schedule_log`
    ADD CONSTRAINT `fk_ds_warmup_plan_schedule_log_delivery_server1` FOREIGN KEY (`server_id`) REFERENCES `delivery_server` (`server_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
    ADD CONSTRAINT `fk_ds_warmup_plan_schedule_log_ds_warmup_plan_schedule1` FOREIGN KEY (`schedule_id`) REFERENCES `delivery_server_warmup_plan_schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
    ADD CONSTRAINT `fk_ds_warmup_plan_schedule_log_ds_warmup_plan1` FOREIGN KEY (`plan_id`) REFERENCES `delivery_server_warmup_plan` (`plan_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Changes for table `delivery_server`
--
ALTER TABLE `delivery_server`
    ADD `warmup_plan_id` INT(11) NULL DEFAULT NULL AFTER `tracking_domain_id`;

ALTER TABLE `delivery_server` ADD KEY `fk_delivery_server_ds_warmup_plan1_idx` (`warmup_plan_id`);

--
-- Constraints for table `delivery_server`
--
ALTER TABLE `delivery_server`
    ADD CONSTRAINT `fk_delivery_server_ds_warmup_plan1` FOREIGN KEY (`warmup_plan_id`) REFERENCES `delivery_server_warmup_plan` (`plan_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

