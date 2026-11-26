            /**
             *
             * 自定义版 客服咨询js
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
                    link.href = YMWL_BASE + '/plugins/flgkf/css/chatStyle.css';
                    head.appendChild(link);
                var script = document.createElement('script');
                    script.type='text/javascript';
                    script.src = YMWL_BASE + '/assets/js/js.cookie.min.js';
                    head.appendChild(script); 
                    var blzx={};
                 window.addEventListener("load", function(){
                        var service_id=Cookies.get('service_id1');
                        var visiter_id=Cookies.get('visiter_id1');
                     if(service_id===undefined){
                        service_id='';
                     }
                     if(visiter_id===undefined){
                        visiter_id='';
                     }
                       blzx={
                visiter_id:(typeof ymwl=='undefined' || typeof ymwl.visiter_id == 'undefined')?visiter_id:ymwl.visiter_id,
                     visiter_name:(typeof ymwl=='undefined' || typeof ymwl.visiter_name == 'undefined')?'':ymwl.visiter_name,
                     avatar:(typeof ymwl=='undefined' || typeof ymwl.avatar == 'undefined')?'':ymwl.avatar,
                     product:(typeof ymwl=='undefined' || typeof ymwl.product == 'undefined')?'{}':ymwl.product,
                     open:function(){
                        var d =document.getElementById('ymwl-showright');
                        if(!d){
                            var div =document.createElement('div');
                            div.id ="ymwl-showright";
                            document.body.appendChild(div);
                            var w =document.getElementById('ymwl-showright');
                            w.innerHTML='<div style="background-color:#05202d" class="ymwl-showright-wrap" onclick=\'blzx.openBox("wolive-talk")\'><div style="height: 100%;width: 100%;"><div class="news-num" id="ymwl-news-num">0</div><div class="ymwl-showright-content-top" style="background-color:#05202d"><div class="ymwl-showright-content-img"></div></div><div class="ymwl-showright-content-button" style="background-color:#05202d">在线客服</div></div></div>';
                        }
                     },
                     connenct:function(groupid){
                      var id =groupid;
                      var web =encodeURI(YMWL_BASE + '/layer?theme=05202d&visiter_id='+blzx.visiter_id+'&visiter_name='+blzx.visiter_name+'&avatar='+blzx.avatar+'&business_id=1&groupid='+groupid+'&special='+service_id+'&product='+blzx.product);
                       var s =document.getElementById('wolive-talk');
                       if(!s){
                            var div = document.createElement('div');
                            div.id ="wolive-talk";
                            div.name=id;
                            if(blzx.isMobile()){
                               div.style.width='100%';
                               div.style.height='80%';
                           }
                            document.body.appendChild(div);
                            div.innerHTML='<i class="blzx-close" onclick="blzx.closeMinChatWindow(\'wolive-talk\')"></i><iframe id="wolive-iframe" src="'+web+'"></iframe>'
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
                     createGreetingBox:function(){
                        var n2 =document.getElementById('ymwl-showhelp');
                        if(!n2){
                            var div2 =document.createElement('div');
                            div2.id ="ymwl-showhelp";
                            div2.innerHTML='<div class="ymwl-showhelp-wrap"><div class="ymwl-showhelp-wrap-inner" ><div class="ymwl-showhelp-content"><div class="ymwl-close" onclick="blzx.closeMinChatWindow(\'ymwl-showhelp\')" >✕</div><div class="ymwl-showhelp-content-text" id="ymwl-showhelp-content-text"><p>您好，有什么可以帮助您？😊</p></div></div></div></div>';
                            document.body.appendChild(div2);
                        }
                     },createNewsBox:function(){
                       var n =document.getElementById('ymwl-shownews');
                        if(!n){
                            var div1 =document.createElement('div');
                            div1.id ="ymwl-shownews";
                             div1.innerHTML='<div style="position: relative"><div class="kailong"></div></div><div id="ymwl-shownews-wrap"><div class="ymwl-showhelp-wrap"><div class="ymwl-showhelp-wrap-inner" onclick="blzx.openBox(\'wolive-talk\')"><div class="ymwl-showhelp-content"><div class="ymwl-news-head"><img id="ymwl-news-head-img"><span id="ymwl-news-head-name">客服昵称</span></div><div class="ymwl-close" onclick="blzx.closeMinChatWindow(\'ymwl-shownews\')">✕</div><div class="ymwl-showhelp-content-text" id="ymwl-shownews-content-text"><p>这里是信息</p></div></div></div></div></div>';
                            document.body.appendChild(div1);
                        }
                     },closeMinChatWindow:function(id){
                        document.getElementById(id).style.display="none";
                    },openBox:function(id){
                    if('wolive-talk'===id){
                        document.getElementById('ymwl-news-num').innerText='0';
                        document.getElementById('ymwl-news-num').style.display="none";
                        //document.getElementById('ymwl-showhelp').style.display="none";
                        document.getElementById('ymwl-shownews').style.display="none";
                    }
                        document.getElementById(id).style.display="block";
                    },isMobile:function(){
                        if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
                            return true;
                        }else{
                            return false;
                        }
                    }
                }
                blzx.open();
                blzx.createNewsBox();
                blzx.connenct(0);
                function handleMessage(event) {
                console.log(event);
                var newsBox=document.getElementById('ymwl-shownews');
                var talkBox=document.getElementById('wolive-talk');
                 var oNewsNum=document.getElementById('ymwl-news-num');
                var content=event.data.data.content;
                Cookies.set('service_id1',event.data.data.service_id,{ expires: 999999999, path: '/'});
                Cookies.set('visiter_id1',event.data.data.visiter_id,{ expires: 999999999, path: '/'});
                var origin=event.origin;
                content=content.replace('="/', '="'+origin+"/");
                var avatar=event.data.data.avatar;
                avatar=avatar.replace(/^\//,origin+"/");
                console.dir(event.data.data);
                var type = event.data.type;
                var value = event.data.value;
                var domain = event.data.domain;
               if(event.data.type==='greeting'){
               console.log('推送问候');
                    if(1 && newsBox && (talkBox.style.display==='none' || talkBox.style.display==='') ){ 
                    var cookie_showGreeting=Cookies.get('showGreeting');
                        if(cookie_showGreeting===undefined || cookie_showGreeting<1){
                        setTimeout(function(){ 
                            document.getElementById('ymwl-shownews-content-text').innerHTML=content;
                             document.getElementById('ymwl-news-head-name').innerText=event.data.data.nick_name;
                             document.getElementById('ymwl-news-head-img').src=avatar;
                             newsBox.style.display="block";
                            var inFifteenMinutes = new Date(new Date().getTime() + 12 * 60 * 60 * 1000);
                         Cookies.set('showGreeting',1,{ expires: inFifteenMinutes, path: '/'});
                         }, 0);
                         }
                    }
               }else if(event.data.type==='sendNews'){
               console.log('收到发送过来的信息了');
               console.log(talkBox.style.display);
                    if(newsBox && (talkBox.style.display==='none' || talkBox.style.display==='')){
                     document.getElementById('ymwl-shownews-content-text').innerHTML=content;
                     document.getElementById('ymwl-news-head-name').innerText=event.data.data.nick_name;
                     document.getElementById('ymwl-news-head-img').src=avatar;
                     newsBox.style.display="block";
                     oNewsNum.innerText=parseInt(oNewsNum.innerText)+1;
                     oNewsNum.style.display="block";
                    }
               }
            }
            window.addEventListener('message', handleMessage, false);
                
                
                 });      
               
              
