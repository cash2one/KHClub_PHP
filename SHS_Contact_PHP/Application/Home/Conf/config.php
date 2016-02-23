<?php
return array(
	//'配置项'=>'配置值'
    'DB_TYPE'   => 'mysql', // 数据库类型
    'DB_HOST'   => '114.215.95.23', // 服务器地址
    'DB_NAME'   => 'mk', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => 'khclub2015', // 密码
    'DB_CHARSET'=> 'utf8mb4',
    'DB_PORT'   => 3306, // 端口

    /* URL设置 */
    'URL_CASE_INSENSITIVE'  =>  true,   // 默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'             =>  1,       // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式
    'URL_PATHINFO_DEPR'     =>  '/',	// PATHINFO模式下，各参数之间的分割符号

);