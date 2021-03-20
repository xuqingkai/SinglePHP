<!DOCTYPE html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- 上述3个meta标签*必须*放在最前面，任何其他内容都*必须*跟随其后！ -->
    <title>企业微信部门分类选择器</title>

    <!-- 需要引用的CSS -->
	<link rel="stylesheet" type="text/css" href="/static/css/bootstrap.css" />
	<link rel="stylesheet" type="text/css" href="http://www.jq22.com/jquery/font-awesome.4.6.0.css">
	<link rel="stylesheet" type="text/css" href="/static/css/doublebox-bootstrap.css" />
	<style>
  .ue-container {
	   width: 60%;
	   margin: 0 auto;
	   margin-top: 3%;
	   padding: 20px 40px;
	   border: 1px solid #ddd;
	   background: #fff;
   }
	</style>
	
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="http://cdn.bootcss.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="http://cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
	<!-- 页面结构 -->
	<div class="ue-container">
    <select class="depmt_select"></select>
	    <select multiple="multiple" size="10" name="doublebox" class="demo">
        </select>
	</div>
    <!-- 需要引用的JS -->
   <script src="http://www.jq22.com/jquery/jquery-1.10.2.js"></script>
    <script type="text/javascript" src="/static/js/bootstrap.js"></script>
    <script type="text/javascript" src="/static/js/doublebox-bootstrap.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
		  var demo2 = $('.demo').doublebox({
          nonSelectedListLabel: '选择角色',
          selectedListLabel: '授权用户角色',
          preserveSelectionOnMove: 'moved',
          moveOnSelect: false,
          nonSelectedList:[{"roleId":"1","roleName":"zhangsan"},{"roleId":"2","roleName":"lisi"},{"roleId":"3","roleName":"wangwu"}],
          selectedList:[{"roleId":"4","roleName":"zhangsan1"},{"roleId":"5","roleName":"lisi1"},{"roleId":"6","roleName":"wangwu1"}],
          optionValue:"roleId",
          optionText:"roleName",
          doubleMove:true,
        });
        })
      </script>
  </body>
</html>