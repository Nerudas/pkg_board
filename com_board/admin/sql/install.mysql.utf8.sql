CREATE TABLE IF NOT EXISTS `#__board_categories` (
  `id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `title`     VARCHAR(255) NOT NULL DEFAULT '',
  `parent_id` INT(11)      NOT NULL DEFAULT '0',
  `lft`       INT(11)      NOT NULL DEFAULT '0',
  `rgt`       INT(11)      NOT NULL DEFAULT '0',
  `level`     INT(10)      NOT NULL DEFAULT '0',
  `path`      VARCHAR(400) NOT NULL DEFAULT '',
  `alias`     VARCHAR(400) NOT NULL DEFAULT '',
  `attribs`   TEXT         NOT NULL DEFAULT '',
  `icon`      TEXT         NOT NULL DEFAULT '',
  `state`     TINYINT(3)   NOT NULL DEFAULT '0',
  `metakey`   MEDIUMTEXT   NOT NULL DEFAULT '',
  `metadesc`  MEDIUMTEXT   NOT NULL DEFAULT '',
  `access`    INT(10)      NOT NULL DEFAULT '0',
  `metadata`  MEDIUMTEXT   NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 0;

CREATE TABLE IF NOT EXISTS `#__board_items` (
  `id`             INT(11)          NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(255)     NOT NULL DEFAULT '',
  `text`           LONGTEXT         NOT NULL DEFAULT '',
  `contacts`       MEDIUMTEXT       NOT NULL DEFAULT '',
  `images`         MEDIUMTEXT       NOT NULL DEFAULT '',
  `state`          TINYINT(3)       NOT NULL DEFAULT '0',
  `created`        DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by`     INT(11)          NOT NULL DEFAULT '0',
  `for_when`       VARCHAR(100)     NOT NULL DEFAULT '',
  `actual`         VARCHAR(100)     NOT NULL DEFAULT '',
  `publish_down`   DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
  `map`            TEXT             NOT NULL DEFAULT '',
  `latitude`       DOUBLE(20, 6),
  `longitude`      DOUBLE(20, 6),
  `price`          TEXT             NOT NULL DEFAULT '',
  `payment_method` VARCHAR(100)     NOT NULL DEFAULT '',
  `prepayment`     VARCHAR(100)     NOT NULL DEFAULT '',
  `attribs`        TEXT             NOT NULL DEFAULT '',
  `metakey`        MEDIUMTEXT       NOT NULL DEFAULT '',
  `metadesc`       MEDIUMTEXT       NOT NULL DEFAULT '',
  `access`         INT(10)          NOT NULL DEFAULT '0',
  `hits`           INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `region`         CHAR(7)          NOT NULL DEFAULT '*',
  `metadata`       MEDIUMTEXT       NOT NULL DEFAULT '',
  `tags_search`    MEDIUMTEXT       NOT NULL DEFAULT '',
  `tags_map`       LONGTEXT         NOT NULL DEFAULT '',
  `extra`          LONGTEXT         NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 0;
