/**
 * 连接websockt
 */

var _pusher_instance = null;
var _wolive_connecting = false;

var wolive_connect=function(){

    if (_wolive_connecting) {
        return;
    }
    _wolive_connecting = true;

    if (_pusher_instance) {
        try {
            _pusher_instance.unsubscribe("kefu" + config.service_id);
            _pusher_instance.unsubscribe("all" + config.business_id);
            _pusher_instance.unsubscribe("ud" + config.service_id);
            _pusher_instance.disconnect();
        } catch(e) {}
        _pusher_instance = null;
    }

    var pusher;

      if(config.value == 'true'){
         pusher = new Pusher(config.app_key, {
                encrypted: true
                , enabledTransports: ['wss']
                , wsHost: config.web_host
                , wssPort: config.web_port
                , authEndpoint: '/auth.php'
                ,disableStats: true
         });
      }else{
        pusher = new Pusher(config.app_key, {
                encrypted: false
                , enabledTransports: ['ws']
                , wsHost: config.web_host
                , wsPort: config.web_port
                , authEndpoint: '/auth.php'
                ,disableStats: true
         });
      }

     // 私人频道
     var channelme = pusher.subscribe("ud" + config.service_id);
        channelme.bind("on_notice", function (data) {
          if(data.message.type == 'change'){
              layer.msg(data.message.msg);
            }
        });

        channelme.bind("on_chat", function (data) {
            $.cookie("cu_com",'');
            layer.msg('该访客被删除');
        });

    // 公共频道
    var channelall = pusher.subscribe("all" + config.business_id);
       channelall.bind("on_notice", function (data) {
           $("#wolive_notice").removeClass('hide');
       });    

    // 对话频道
    var channel =pusher.subscribe("kefu" + config.service_id);
       // 接受消息
       channel.bind("cu-event", function (data) {
        $("#wolive_notice").removeClass('hide');
       });  
           // 已读标识
    channel.bind("check-event", function (data) {
        $.each(data.message,function(index,value){
            $("#cid"+value).html("已读");
        });
    });

    // websocket重连
     pusher.connection.bind('state_change', function(states) {
      
      if(states.current == 'unavailable' || states.current == "disconnected" || states.current == "failed" ){
           try {
               pusher.unsubscribe("kefu"+config.service_id);
               pusher.unsubscribe("all"+config.business_id);
               pusher.unsubscribe("ud"+config.service_id);
               pusher.disconnect();
           } catch(e) {}
           _pusher_instance = null;
           _wolive_connecting = false;
           wolive_connect();
        }

    });

    // 保存pusher实例供后续使用
    _pusher_instance = pusher;
    window._pusher = pusher;
    _wolive_connecting = false;

  } 
  
  wolive_connect();

// 页面可见性变化监听 - 客服端从后台恢复前台时刷新数据
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        console.log('客服页面恢复前台，刷新数据...');
        wolive_connect();
        
        // 刷新聊天列表
        if (typeof getchat === 'function') {
            getchat();
        }
        
        // 刷新当前对话数据
        if (typeof getdata === 'function') {
            var currentVisiter = $.cookie('cu_com');
            if (currentVisiter) {
                try {
                    var visiterData = $.parseJSON(currentVisiter);
                    if (visiterData && visiterData.visiter_id) {
                        getdata(visiterData.visiter_id);
                    }
                } catch(e) {}
            }
        }
    }
});

// 网络状态监听 - 客服端网络恢复时重连
window.addEventListener('online', function() {
    console.log('客服网络恢复，重连WebSocket...');
    wolive_connect();
    
    // 刷新聊天列表
    if (typeof getchat === 'function') {
        getchat();
    }
});

// 页面显示事件 - 从bfcache恢复时刷新
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        console.log('客服页面从缓存恢复，刷新数据...');
        wolive_connect();
        if (typeof getchat === 'function') {
            getchat();
        }
    }
});

