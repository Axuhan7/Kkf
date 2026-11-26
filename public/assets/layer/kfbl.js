
            /**
             *
             * wolive 浮层版 客服咨询js
             * [wolive description]
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
    link.href = YMWL_BASE + '/assets/css/index/cgwl_online.css';
    head.appendChild(link);
var cgwl ={
     visiter_id:'',
     visiter_name:'',
     avatar:'',
     product:'',
     connenct:function(groupid){
      var id =groupid;
      var web =encodeURI(YMWL_BASE + '/layer?visiter_id='+this.visiter_id+'&visiter_name='+this.visiter_name+'&avatar='+this.avatar+'&business_id=2&groupid='+groupid+'&product='+this.product);
      var moblieweb = encodeURI(YMWL_BASE + '/mobile/index/home?visiter_id='+this.visiter_id+'&visiter_name='+this.visiter_name+'&avatar='+this.avatar+'&business_id=2&groupid='+groupid+'&product='+this.product);
       if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
         window.open(moblieweb); 
       }else{
       var s =document.getElementById('wolive-talk');
       if(!s){
            var div = document.createElement('div');
            div.id ="wolive-talk";
            div.name=id;
            document.body.appendChild(div);
            div.innerHTML='<i class="cgwl-close" onclick="cgwl.narrow()"></i><iframe id="wolive-iframe" src="'+web+'"></iframe>'
        }else{
            var title =s.name;
            if(title == groupid){
                s.style ='display:block';
            }else{
                s.parentNode.removeChild(s);
                cgwl.connenct(groupid); 
            }
        }
      }
     },
     narrow:function(){
        document.getElementById('wolive-talk').style="display:none";
     }
}
window.onload =cgwl.open();
