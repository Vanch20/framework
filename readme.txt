1. 命名规则
控制器: 控制器名称+controller.php 例: testcontroller.php
控制器方法: action+方法名 例: actionTest();


2.异常的使用
throw new Exception('message'); 将调用CommonException

404错误: 
Loader::exception('notfind');
throw new NotFindException();