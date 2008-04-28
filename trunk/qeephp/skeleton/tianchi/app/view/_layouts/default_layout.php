<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<title>QeePHP: Welcome aboard</title>
<style type="text/css" media="screen">
body {
    margin: 0;
    margin-bottom: 25px;
    padding: 0;
    background-color: #f0f0f0;
    font-family: "Lucida Grande", "Bitstream Vera Sans", "Verdana";
    font-size: 13px;
    color: #333;
}
h1 {
    font-size: 28px;
    color: #000;
}
a {
    color: #03c
}
a:hover {
    background-color: #03c;
    color: white;
    text-decoration: none;
}
#page {
    background-color: #f0f0f0;
    width: 750px;
    margin: 0;
    margin-left: auto;
    margin-right: auto;
}
#content {
    float: left;
    background-color: white;
    border: 3px solid #aaa;
    border-top: none;
    padding: 25px;
    width: 500px;
}
#sidebar {
    float: right;
    width: 175px;
}
#footer {
    clear: both;
}
#header, #getting-started {
    padding-left: 75px;
    padding-right: 30px;
}
#header {
    background-image: url("%MACRO:PUBLIC_ROOT%img/qeephp.png");
    background-repeat: no-repeat;
    background-position: top left;
    height: 64px;
}
#header h1, #header h2 {
    margin: 0
}
#header h2 {
    color: #888;
    font-weight: normal;
    font-size: 16px;
}
#about h3 {
    margin: 0;
    margin-bottom: 10px;
    font-size: 14px;
}
#about-content {
    background-color: #ffd;
    border: 1px solid #fc0;
    margin-left: -11px;
}
#about-content table {
    margin-top: 10px;
    margin-bottom: 10px;
    font-size: 11px;
    border-collapse: collapse;
}
#about-content td {
    padding: 10px;
    padding-top: 3px;
    padding-bottom: 3px;
}
#about-content td.name {
    color: #555
}
#about-content td.value {
    color: #000
}
#about-content.failure {
    background-color: #fcc;
    border: 1px solid #f00;
}
#about-content.failure p {
    margin: 0;
    padding: 10px;
}
#getting-started {
    border-top: 1px solid #ccc;
    margin-top: 25px;
    padding-top: 15px;
}
#getting-started h1 {
    margin: 0;
    font-size: 20px;
}
#getting-started h2 {
    margin: 0;
    font-size: 14px;
    font-weight: normal;
    color: #333;
    margin-bottom: 25px;
}
#getting-started ol {
    margin-left: 0;
    padding-left: 0;
}
#getting-started li {
    font-size: 18px;
    color: #888;
    margin-bottom: 25px;
}
#getting-started li h2 {
    margin: 0;
    font-weight: normal;
    font-size: 18px;
    color: #333;
}
#getting-started li p {
    color: #555;
    font-size: 13px;
}
#search {
    margin: 0;
    padding-top: 10px;
    padding-bottom: 10px;
    font-size: 11px;
}
#search input {
    font-size: 11px;
    margin: 2px;
}
#search-text {
    width: 170px
}
#sidebar ul {
    margin-left: 0;
    padding-left: 0;
}
#sidebar ul h3 {
    margin-top: 25px;
    font-size: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ccc;
}
#sidebar li {
    list-style-type: none;
}
#sidebar ul.links li {
    margin-bottom: 5px;
}
</style>
</head>
<body>

<?php echo $contents_for_layouts; // 输出控制器动作动作对应的视图内容 ?>

</body>
</html>