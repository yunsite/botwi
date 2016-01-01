Twittter代理API，效果等于Twitter没有关闭Basic，无视oAuth，直接向此API发送用户密码就可以获得Twitter内容。

### 简单使用： ###
需要一个支持curl/.htaccess/无广告的PHP5空间，可以不做任何设置，上传即可用，API地址指向上传目录下的/t。

可以运行check.php检查一下主机是否支持。

### 自定义API名称： ###
比如你上传到了 http://www.abc.com/botwi/ 下面，那么你在 http://dev.twitter.com 申请的应用的 OAUTH\_CALLBACK\_URL要指向 http://www.abc.com/botwi/oauth ，在botwi/t/config.php里定义这个值，并按申请应用时给你的值修改OAUTH\_CONSUMER\_KEY和OAUTH\_CONSUMER\_SECRET。

### 更快地连接： ###
如果传给API的是用户名密码，那么除了几个不需要认证的search之类，一般都要自动运算一次获得oAuth授权，但如果你能让客户端发来oauth\_token和oauth\_token\_secret，那就可以省下这一步自动运算的时间。

我自己定义了一个/takeoAuth.json，向/botwi/t/takeoAuth.json发送用户密码，你就可以得到一个json格式的oauth\_token和oauth\_token\_secret，然后你就可以接下来都用这两个东西发送请求了。

个人精力有限，开源也是为了希望有心人帮忙，比如测试一下，如果你测试的客户端下面没有，麻烦你将测试结果上推＠iamzzm说一下：

**经测试通过的客户端**

中文推特圈、真理部内参、博微博（M8/Android）、Salt（M8）、Echofon、Twhirl（修正了一个错误，见SVN）、Seesmic、twidroyed、twitbird for free（iphone）...

**经测试不通过待修正的客户端**

Mixero、Twitter for iPhone最新版本...