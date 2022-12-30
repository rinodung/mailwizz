--
-- Update sql for MailWizz EMA from version 2.0.9 to 2.0.10
--

--
-- Table structure for table `list_open_graph`
--

DROP TABLE IF EXISTS `list_open_graph`;
CREATE TABLE IF NOT EXISTS `list_open_graph` (
    `list_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `image` varchar(255) NULL,
    `description` varchar(255) NOT NULL,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`list_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Constraints for table `list_open_graph`
--
ALTER TABLE `list_open_graph`
    ADD CONSTRAINT `fk_list_open_graph_list1` FOREIGN KEY (`list_id`) REFERENCES `list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;
