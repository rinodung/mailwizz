--
-- Update sql for MailWizz EMA from version 2.1.10 to 2.1.11
--

--
-- Table structure for table `favorite_page`
--

DROP TABLE IF EXISTS `favorite_page`;
CREATE TABLE IF NOT EXISTS `favorite_page` (
    `page_id` int(11) NOT NULL AUTO_INCREMENT,
    `page_uid` char(13) NOT NULL,
    `user_id` int(11) NULL DEFAULT NULL,
    `customer_id` int(11) NULL DEFAULT NULL,
    `label` varchar(255) NOT NULL,
    `route` varchar(255) NOT NULL,
    `route_params` BLOB NULL,
    `route_hash` char(40) NOT NULL,
    `clicks_count` int(11) NOT NULL DEFAULT 0,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`page_id`),
    UNIQUE KEY `page_uid` (`page_uid`),
    KEY `fk_favorite_page_customer1_idx` (`customer_id`),
    KEY `fk_favorite_page_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Constraints for table `favorite_page`
--
ALTER TABLE `favorite_page`
    ADD CONSTRAINT `fk_favorite_page_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
    ADD CONSTRAINT `fk_favorite_page_customer1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;
