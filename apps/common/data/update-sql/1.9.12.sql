--
-- Update sql for MailWizz EMA from version 1.9.11 to 1.9.12
--

DROP TABLE IF EXISTS `list_segment_campaign_condition`;
CREATE TABLE IF NOT EXISTS `list_segment_campaign_condition` (
  `condition_id` int(11) NOT NULL AUTO_INCREMENT,
  `segment_id` int(11) NOT NULL,
  `campaign_id` int(11) NULL DEFAULT NULL,
  `action` varchar(255) NOT NULL DEFAULT 'open',
  `action_click_url_id` bigint(11) NULL DEFAULT NULL,
  `time_value` int(11) NOT NULL DEFAULT 60,
  `time_unit` varchar(20) NOT NULL DEFAULT 'day',
  `time_comparison_operator` varchar(20) NOT NULL DEFAULT 'lte',  
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`condition_id`),
  KEY `fk_list_segment_campaign_condition_list_segment1_idx` (`segment_id`),
  KEY `fk_list_segment_campaign_condition_campaign1_idx` (`campaign_id`),
  KEY `fk_list_segment_campaign_condition_action_click_url1_idx`(`action_click_url_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `list_segment_campaign_condition`
  ADD CONSTRAINT `fk_list_segment_campaign_condition_list_segment1` FOREIGN KEY (`segment_id`) REFERENCES `list_segment` (`segment_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_list_segment_campaign_condition_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_list_segment_campaign_condition_action_click_url1` FOREIGN KEY (`action_click_url_id`) REFERENCES `campaign_track_url` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
