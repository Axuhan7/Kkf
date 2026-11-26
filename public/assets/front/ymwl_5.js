
             
             /**
              *
              * wolive 标准窗口,js代码
              * 
              */

            // 自动检测脚本所在域名（支持任何域名部署，无需硬编码）
            var YMWL_BASE = (function(){
                try {
                    var s = document.currentScript || (function(){
                        var ss = document.getElementsByTagName('script');
                        return ss[ss.length - 1];
                    })();
                    if (s && s.src) {
                        var m = s.src.match(/^(https?:\/\/[^\/]+)/i);
                        if (m) return m[1];
                    }
                } catch(e) {}
                return window.location.protocol + '//' + window.location.host;
            })();

            var head = document.getElementsByTagName('head')[0];
            var link = document.createElement('link');
                link.type='text/css';
                link.rel = 'stylesheet';
                link.href = YMWL_BASE + '/assets/css/index/ymwl_online.css';
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
                      div.style.backgroundColor='#12bf51'
                      document.body.appendChild(div);
                      var w =document.getElementById('ymwl-kefu');
                      w.innerHTML=' <i class="ymwl-icon"></i><p class="ymwl-item" onclick="blzx.blank(0)" >在线咨询</p>';
                    }

                 },
                 blank:function(groupid){
                  var web =encodeURI(YMWL_BASE + '/index/index/home?theme=12bf51&visiter_id='+blzx.visiter_id+'&visiter_name='+blzx.visiter_name+'&avatar='+blzx.avatar+'&business_id=5&groupid='+groupid+'&product='+blzx.product);
                      
                  var moblieweb = encodeURI(YMWL_BASE + '/mobile/index/home?theme=12bf51&visiter_id='+blzx.visiter_id+'&visiter_name='+blzx.visiter_name+'&avatar='+blzx.avatar+'&business_id=5&groupid='+groupid+'&product='+blzx.product);

                   if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
                     window.open(moblieweb); 
                   }else{
                     window.open(web); 
                   }
                 },
            }

            window.onload = blzx.open();
        
