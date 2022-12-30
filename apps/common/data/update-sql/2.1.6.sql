--
-- Update sql for MailWizz EMA from version 2.1.5 to 2.1.6
--

--
-- Table structure for table `customer_ip_blacklist`
--

DROP TABLE IF EXISTS `customer_ip_blacklist`;
CREATE TABLE IF NOT EXISTS `customer_ip_blacklist` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `customer_id_ip_address_UNIQUE` (`customer_id`, `ip_address`),
    KEY `fk_customer_ip_blacklist_customer1_idx` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;

--
-- Constraints for table `customer_ip_blacklist`
--
ALTER TABLE `customer_ip_blacklist`
    ADD CONSTRAINT `fk_customer_ip_blacklist_customer1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;
