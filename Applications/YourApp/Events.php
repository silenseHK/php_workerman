<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {

        // 向当前client_id发送数据 
        Gateway::sendToClient($client_id, returnJson('LOGIN',['client_id'=>$client_id]));
        // 向所有人发送

//        Gateway::sendToAll();
        //获取当前房间的所有人数
        $members = Gateway::getAllClientIdList();
        Gateway::sendToAll(returnJson('MEMBERS',['members'=>$members]));
    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message)
   {
       $message_data = json_decode($message, true);

       if(!$message_data['type'])
           throw new Exception('请求异常');

       $type = $message_data['type'];

       switch($type){

           case 'ping':
               return;
           case 'BIND_UID':
               Gateway::bindUid($client_id,(int)$message_data['uid']);
               echo $client_id;
               Gateway::sendToClient($client_id,returnJson('TIP','uid绑定成功'));
               Gateway::sendToAll(returnJson('INFO','欢迎uid为'.(int)$message_data['uid'].'的用户加入房间'));
               break;
           case 'TALK':
               $uid = Gateway::getUidByClientId($client_id);
               Gateway::sendToAll(returnJson('MESSAGE',['uid'=>$uid,'message'=>$message_data['message']]));
               break;
           case 'DEL_MEMBER':
               Gateway::closeClient($message_data['client_id']);
               break;
       }

        // 向所有人发送 
//        Gateway::sendToAll("$client_id said $message\r\n");
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
       $uid = Gateway::getUidByClientId($client_id);
       // 向所有人发送 
       GateWay::sendToAll(returnJson('INFO',"用户{$uid}离开房间"));

       $members = Gateway::getAllClientIdList();
       Gateway::sendToAll(returnJson('MEMBERS',['members'=>$members]));
   }
}

function returnJson($type,$data){
    return "'".json_encode(compact('type','data'))."'";
}


