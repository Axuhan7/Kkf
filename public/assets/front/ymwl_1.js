var __YMWL_HOST=(typeof location!=='undefined'&&location.origin)?location.origin:'';

             
             /**
              *
              * wolive 标准窗口,js代码
              * 
              */
             
            var head = document.getElementsByTagName('head')[0];
            var link = document.createElement('link');
                link.type='text/css';
                link.rel = 'stylesheet';
                link.href =__YMWL_HOST + '/assets/css/index/ymwl_online.css';
                head.appendChild(link);

            var blzx ={
                visiter_id:(typeof ymwl=='undefined' || typeof ymwl.visiter_id == 'undefined')?'':ymwl.visiter_id,
                 visiter_name:(typeof ymwl=='undefined' || typeof ymwl.visiter_name == 'undefined')?'':ymwl.visiter_name,
                 avatar:(typeof ymwl=='undefined' || typeof ymwl.avatar == 'undefined')?'':ymwl.avatar,
                 product:(typeof ymwl=='undefined' || typeof ymwl.product == 'undefined')?'{}':ymwl.product,
                 open:function(){
                    var d =document.getElementById('wolive-box');
                    if(!d){
                      var div =document.createElement('div');
                      div.id ='ymwl-kefu';
                      div.className +='ymwl-form';
                      div.style.backgroundColor='#6a59ff'
                      document.body.appendChild(div);
                      var w =document.getElementById('ymwl-kefu');
                      w.innerHTML=' <i class="ymwl-icon"></i><p class="ymwl-item" onclick="blzx.blank(0)" >在线咨询</p>';
                    }

                 },
                 blank:function(groupid){
                  var web =encodeURI(__YMWL_HOST + '/index/index/home?theme=6a59ff&visiter_id='+blzx.visiter_id+'&visiter_name='+blzx.visiter_name+'&avatar='+blzx.avatar+'&business_id=1&groupid='+groupid+'&product='+blzx.product);
                      
                  var moblieweb = encodeURI(__YMWL_HOST + '/mobile/index/home?theme=6a59ff&visiter_id='+blzx.visiter_id+'&visiter_name='+blzx.visiter_name+'&avatar='+blzx.avatar+'&business_id=1&groupid='+groupid+'&product='+blzx.product);

                   if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
                     window.open(moblieweb); 
                   }else{
                     window.open(web); 
                   }
                 },
            }

            window.onload = blzx.open();
        