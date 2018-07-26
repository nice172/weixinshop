alter table ccl_users add wxuser varchar(255) not null default '' comment '微信号';
alter table ccl_users add province varchar(255) not null default '' comment '省';
alter table ccl_users add city varchar(255) not null default '' comment '市';
alter table ccl_users add area varchar(255) not null default '' comment '区';
alter table ccl_users add truename varchar(255) not null default '' comment '姓名';
alter table ccl_order_quick add audio varchar(255) not null default '' comment '录音文件';




