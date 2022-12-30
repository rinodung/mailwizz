--
-- Update sql for MailWizz EMA from version 2.0.33 to 2.0.34
--

--
-- Table structure for table `list_subscriber_count_history`
--
DROP TABLE IF EXISTS `list_subscriber_count_history`;
CREATE TABLE IF NOT EXISTS `list_subscriber_count_history` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `list_id` int(11) NOT NULL,
    `total` int(11) NOT NULL DEFAULT 0,
    `confirmed_total` int(11) NOT NULL DEFAULT 0,
    `unconfirmed_total` int(11) NOT NULL DEFAULT 0,
    `blacklisted_total` int(11) NOT NULL DEFAULT 0,
    `unsubscribed_total` int(11) NOT NULL DEFAULT 0,
    `moved_total` int(11) NOT NULL DEFAULT 0,
    `disabled_total` int(11) NOT NULL DEFAULT 0,
    `unapproved_total` int(11) NOT NULL DEFAULT 0,
    `confirmed_hourly` int(11) NOT NULL DEFAULT 0,
    `unconfirmed_hourly` int(11) NOT NULL DEFAULT 0,
    `blacklisted_hourly` int(11) NOT NULL DEFAULT 0,
    `unsubscribed_hourly` int(11) NOT NULL DEFAULT 0,
    `moved_hourly` int(11) NOT NULL DEFAULT 0,
    `disabled_hourly` int(11) NOT NULL DEFAULT 0,
    `unapproved_hourly` int(11) NOT NULL DEFAULT 0,
    `date_added` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_list_subscriber_count_history_list1_idx` (`list_id`),
    KEY `list_id_date_added` (`list_id`,`date_added`)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Constraints for table `list_subscriber_count_history`
--
ALTER TABLE `list_subscriber_count_history`
    ADD CONSTRAINT `fk_list_subscriber_count_history1` FOREIGN KEY (`list_id`) REFERENCES `list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;
