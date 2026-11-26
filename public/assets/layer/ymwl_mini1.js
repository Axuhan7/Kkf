            /**
             *
             * 浮层版 客服咨询js
             * @return {[type]} [description]
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
                var script = document.createElement('script');
                    script.type='text/javascript';
                    script.src = YMWL_BASE + '/assets/js/js.cookie.min.js';
                    head.appendChild(script); 
                     var blzx={};
window.addEventListener('load', function(){
                blzx ={
                    visiter_id:(typeof ymwl=='undefined' || typeof ymwl.visiter_id == 'undefined')?'':ymwl.visiter_id,
                     visiter_name:(typeof ymwl=='undefined' || typeof ymwl.visiter_name == 'undefined')?'':ymwl.visiter_name,
                     avatar:(typeof ymwl=='undefined' || typeof ymwl.avatar == 'undefined')?'':ymwl.avatar,
                     product:(typeof ymwl=='undefined' || typeof ymwl.product == 'undefined')?'{}':ymwl.product,
                     open:function(){
                        var d =document.getElementById('wolive-box');
                        if(!d){
                            var div =document.createElement('div');
                            div.id ="ymwl-kefu";
                            div.className +='ymwl-form';
                            div.style.backgroundColor='#05202d'
                            document.body.appendChild(div);
                            var w =document.getElementById('ymwl-kefu');
                            w.innerHTML='<i class="ymwl-icon"></i><p class="ymwl-item zidong" onclick="blzx.connenct(0)">在线咨询</p>';
                        }
                     },
                     connenct:function(groupid){
                      var id =groupid;
                      var web =encodeURI(YMWL_BASE + '/layer?theme=05202d&visiter_id='+blzx.visiter_id+'&visiter_name='+blzx.visiter_name+'&avatar='+blzx.avatar+'&business_id=1&groupid='+groupid+'&product='+blzx.product);
                      var moblieweb = encodeURI(YMWL_BASE + '/mobile/index/home?theme=05202d&visiter_id='+blzx.visiter_id+'&visiter_name='+blzx.visiter_name+'&avatar='+blzx.avatar+'&business_id=1&groupid='+groupid+'&product='+blzx.product);
                       var s =document.getElementById('wolive-talk');
                       if(!s){

                            var div = document.createElement('div');
                            div.id ="wolive-talk";
                            div.name=id;
                            if(blzx.isMobile()){
                               div.style.width='100%';
                               div.style.bottom=0;
                               div.style.right=0;
                               div.style.left=0;
                               
                           }
                            div.style.backgroundColor='#05202d'
                            document.body.appendChild(div);
                            div.innerHTML='<i class="ymwl-close" onclick="blzx.narrow()"></i><iframe id="wolive-iframe" src="'+web+'"></iframe>';
                        }else{
                            var title =s.name;
                            if(title == groupid){
                                s.style.display ='block';
                            }else{
                                s.parentNode.removeChild(s);
                                blzx.connenct(groupid); 
                            }
                        }
                     },
                     narrow:function(){
                        document.getElementById('wolive-talk').style.display="none";
                     },isMobile:function(){
                        if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
                            return true;
                        }else{
                            return false;
                        }
                    }
                }
                blzx.open();
});
