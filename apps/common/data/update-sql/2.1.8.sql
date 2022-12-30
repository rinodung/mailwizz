--
-- Update sql for MailWizz EMA from version 2.1.7 to 2.1.8
--

--
-- Table structure for table `customer_note`
--

DROP TABLE IF EXISTS `customer_note`;
CREATE TABLE IF NOT EXISTS `customer_note` (
    `note_id` INT NOT NULL AUTO_INCREMENT,
    `note_uid` CHAR(13) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `customer_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NULL,
    `note` LONGBLOB NOT NULL,
    `date_added` DATETIME NOT NULL,
    `last_updated` DATETIME NOT NULL,
    PRIMARY KEY (`note_id`),
    KEY `fk_customer_note_user1_idx` (`user_id`),
    KEY `fk_customer_note_customer1_idx` (`customer_id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Constraints for table `customer_note`
--
ALTER TABLE `customer_note`
    ADD CONSTRAINT `fk_customer_note_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
    ADD CONSTRAINT `fk_customer_note_customer1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;
