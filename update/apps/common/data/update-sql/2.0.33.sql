--
-- Update sql for MailWizz EMA from version 2.0.32 to 2.0.33
--

ALTER TABLE `translation_source_message` CHANGE `message` `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL;

--
-- Table structure for table `transactional_email_attachment`
--

DROP TABLE IF EXISTS `transactional_email_attachment`;
CREATE TABLE IF NOT EXISTS `transactional_email_attachment` (
    `attachment_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `email_id` bigint(20) NOT NULL,
    `file` varchar(255) NOT NULL,
    `name` varchar(100) NOT NULL,
    `size` int(11) NOT NULL DEFAULT '0',
    `extension` CHAR(10) NOT NULL,
    `type` varchar(50) NOT NULL,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`attachment_id`),
    KEY `fk_transactional_email_attachment1_idx` (`email_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Constraints for table `transactional_email_attachment`
--
ALTER TABLE `transactional_email_attachment`
    ADD CONSTRAINT `fk_transactional_email_attachment1` FOREIGN KEY (`email_id`) REFERENCES `transactional_email` (`email_id`) ON DELETE CASCADE ON UPDATE NO ACTION;
