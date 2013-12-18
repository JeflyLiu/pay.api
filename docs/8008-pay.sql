-- 创建表 

CREATE TABLE pay.`account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '账户ID',
  `pwd` varchar(120) NOT NULL COMMENT '支付密码',
--  `salt` char(8) NOT NULL COMMENT '密码盐',
  `balance` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '账户余额',
  `freeze_in` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '收入冻结金额',
  `freeze_out` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '支出冻结金额',
  `status` tinyint(1) NOT NULL DEFAULT '2' COMMENT '状态：0正常，1锁定，2未初始化',
  `last_pwd_rest_time` tinyint(5) NOT NULL DEFAULT '0' COMMENT '密码错误次数',
  `member_id` int(10) NOT NULL COMMENT '所属用户',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  `deleted_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='账户表';

CREATE TABLE pay.`freeze_fund` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `belong_id` int(10) NOT NULL DEFAULT '0' COMMENT '所属账户id',
  `accept_id` int(10) NOT NULL DEFAULT '0' COMMENT '承担账户id',
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  `deleted_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='交易资金冻结表';

CREATE TABLE pay.`account_error` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `account_id` int(10) NOT NULL DEFAULT '0' COMMENT '账户ID',
  `e_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类别：0支付密码错误，',
  `ip` char(20) NOT NULL DEFAULT '' COMMENT '异常IP',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='账户异常记录';

CREATE TABLE pay.`account_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `account_id` int(10) NOT NULL DEFAULT '0' COMMENT '账户ID',
  `rec_type` tinyint(5) NOT NULL DEFAULT '0' COMMENT '类别：0支付，1充值，2提现，3支付充值，4冻结',
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `fund_flow` tinyint(1) NOT NULL DEFAULT '0' COMMENT '资金流：0支出，1收入',
  `note`  varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='账户资金记录表（个人账户可用余额改变）';

CREATE TABLE pay.`inpour` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `account_id` int(10) NOT NULL DEFAULT '0' COMMENT '账户ID',
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0关闭，1进行中，2成功，3失败',
  `bill_sn` varchar(32) NOT NULL DEFAULT '0' COMMENT '流水单号',
  `channels` varchar(50) NOT NULL DEFAULT '' COMMENT '支付渠道（如：支付宝，财付通）',
  `note`  varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  `deleted_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='充值记录表';

CREATE TABLE pay.`draw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `account_id` int(10) NOT NULL DEFAULT '0' COMMENT '账号ID',
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0关闭，1进行中，2成功，3失败',
  `bill_sn` varchar(32) NOT NULL DEFAULT '0' COMMENT '流水单号',
  `card_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '提现方式，0借记卡，1支付宝',
  `card_name` int(10) NOT NULL DEFAULT '0' COMMENT '开户名（淘宝账号）',
  `card_no`  char(30) NOT NULL DEFAULT '' COMMENT '卡号(邮箱账号)',
  `bank_id` int(10) NOT NULL DEFAULT '0' COMMENT '银行id',
  `bank_name` int(10) NOT NULL DEFAULT '0' COMMENT '银行名称',
  `bank_province` int(10) NOT NULL DEFAULT '0' COMMENT '开户行省份',
  `bank_city` int(10) NOT NULL DEFAULT '0' COMMENT '开户行城市',
  `pay_voucher` char(30) NOT NULL DEFAULT '0' COMMENT '打款凭证号',
  `pay_note`  varchar(200) NOT NULL DEFAULT '' COMMENT '打款备注',
  `pay_at` int(10) NOT NULL DEFAULT '0' COMMENT '打款时间',
  `pay_user` int(10) DEFAULT '0' COMMENT '打款人',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  `deleted_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '删除时间',
  PRIMARY KEY  (`id`)
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='提现申请表';

CREATE TABLE pay.`bill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水ID',
  `bill_sn` varchar(32) NOT NULL DEFAULT '0' COMMENT '流水单号',
  `bill_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类别：0交易，1充值，2提现',
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `from_id` int(10) NOT NULL DEFAULT '0' COMMENT '来自账户ID',
  `to_id` int(10) NOT NULL DEFAULT '0' COMMENT '流向账户ID',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  PRIMARY KEY  (`id`),
  KEY `bill_sn` (`bill_sn`) USING BTREE
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='票据流水表（个人账户总金额改变）';

CREATE TABLE pay.`trade` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '交易ID',
  `trade_sn` varchar(32) NOT NULL DEFAULT '0' COMMENT '交易号',
  `trade_class` int(10) NOT NULL DEFAULT '0' COMMENT '交易分类:1购物，2付款，3收款',
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '应付金额',
  `has_fee` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '已付金额',
  `not_fee` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '未付金额',
  `total_fee` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `from_id` int(10) NOT NULL DEFAULT '0' COMMENT '来自账户ID',
  `to_id` int(10) NOT NULL DEFAULT '0' COMMENT '流向账户ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '交易状态：0关闭，1待付款，2部分支付，3支付完成，4已经发货，5确认收货，6申请退款，7退款,8失败',
  `bill_pay` varchar(32) NOT NULL DEFAULT '0' COMMENT '支付流水单号',
  `bill_refund` varchar(32) NOT NULL DEFAULT '0' COMMENT '退款流水单号',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  `deleted_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '删除时间',
  PRIMARY KEY  (`id`),
  KEY `trade_sn` (`trade_sn`) USING BTREE
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='交易表';

CREATE TABLE pay.`error_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水ID',
  `obj_id` int(10) NOT NULL DEFAULT '0' COMMENT '对象ID',
  `e_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '异常类别：1账户，2交易，3提现，4充值',
  `code`  char(20) NOT NULL DEFAULT '' COMMENT '异常类型：（如：A0011账户异常，T001异常交易，D001提现异常，I001充值异常）',
  `ip` char(20) NOT NULL DEFAULT '' COMMENT '异常IP',
  `dispose` tinyint(1) NOT NULL DEFAULT '0' COMMENT '处理状态：0等待，1进行，2完成，3失败，4关闭',
  `note` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  PRIMARY KEY  (`id`)
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='异常表';

CREATE TABLE pay.`union_bank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `use_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '卡类别，1充值，2提现',
  `inst_id` int(10) NOT NULL DEFAULT '' COMMENT '银行ID',
  `inst_name` varchar(30) NOT NULL DEFAULT '0' COMMENT '银行名称',
  `alias_name` varchar(30) NOT NULL DEFAULT '0' COMMENT '别名',
  `inst_code` char(20) NOT NULL DEFAULT '' COMMENT '银行码(如：招商银行=CMB)',
  `icon` varchar(50) NOT NULL DEFAULT '' COMMENT '银行图标',
  `disabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '失效：0否，1是',
  `sort` tinyint(5) NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='合作银行';

INSERT INTO pay.`union_bank` (`id`, `use_type`, `inst_id`, `inst_name`,`alias_name`, `inst_code`, `icon`,`disabled`,`sort`,`created_at`,`updated_at`) VALUES
(null,1,1,'支付宝','支付宝','ALIPAY',null,null,null,NOW(),NOW()),
(null,1,2,'中国银行','中国银行','BOC',null,null,null,NOW(),NOW()),
(null,1,3,'招商银行','招商银行','CMB',null,null,null,NOW(),NOW()),
(null,1,4,'工商银行','工商银行','ICBC',null,null,null,NOW(),NOW()),
(null,1,5,'建设银行','建设银行','CCB',null,null,null,NOW(),NOW()),
(null,1,6,'农业银行','农业银行','ABC',null,null,null,NOW(),NOW()),
(null,1,7,'浦发银行','浦发银行','SPDB',null,null,null,NOW(),NOW()),
(null,1,8,'邮政银行','邮政银行','PSBC',null,null,null,NOW(),NOW()),
(null,1,9,'民生银行','民生银行','CMBC',null,null,null,NOW(),NOW()),
(null,1,10,'兴业银行','兴业银行','CIB',null,null,null,NOW(),NOW()),
(null,1,11,'中信银行','中信银行','CITIC',null,null,null,NOW(),NOW()),
(null,1,12,'东亚银行','东亚银行','HKBEA',null,null,null,NOW(),NOW()),
(null,1,13,'光大银行','光大银行','CEB',null,null,null,NOW(),NOW()),
(null,1,14,'广发银行','广发银行','GDB',null,null,null,NOW(),NOW()),
(null,1,15,'杭州银行','杭州银行','HZCB',null,null,null,NOW(),NOW()),
(null,1,16,'交通银行','交通银行','COMM',null,null,null,NOW(),NOW()),
(null,1,17,'宁波银行','宁波银行','NBBANK',null,null,null,NOW(),NOW()),
(null,1,18,'平安银行','平安银行','SPABANK',null,null,null,NOW(),NOW()),
(null,1,19,'上海银行','上海银行','SHBANK',null,null,null,NOW(),NOW())
;

-- 备用表

CREATE TABLE pay.`draw_bank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `bank_id` int(10) NOT NULL DEFAULT '0' COMMENT '银行ID',
  `bank_name` int(10) NOT NULL DEFAULT '0' COMMENT '银行名称',
  `disabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '失效：0否，1是',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='提现银行';

CREATE TABLE pay.`draw_card` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `account_id` int(10) NOT NULL DEFAULT '0' COMMENT '账户ID',
  `card_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '卡片类型，0借记卡，1支付宝',
  `card_name` varchar(30) NOT NULL DEFAULT '' COMMENT '开户名（支付宝账号）',
  `card_no`  varchar(30) NOT NULL DEFAULT '' COMMENT '卡号(邮箱账号)',
  `bank_id` int(10) NOT NULL DEFAULT '0' COMMENT '银行id',
  `bank_name` int(10) NOT NULL DEFAULT '0' COMMENT '银行名称',
  `bank_province` int(10) NOT NULL DEFAULT '0' COMMENT '开户行省份',
  `bank_city` int(10) NOT NULL DEFAULT '0' COMMENT '开户行城市',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认，0否，1是',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='提现卡';




