var e = {
    '你好': 'emo_01',
    '偷笑': 'emo_02',
    '可怜': 'emo_03',
    '惊讶': 'emo_04',
    '敲你': 'emo_05',
    '帅气': 'emo_06',
    '害羞': 'emo_07',
    '阴险': 'emo_08',
    '拜拜': 'emo_09',
    '大哭': 'emo_10',
    '亲亲': 'emo_11',
    '发怒': 'emo_12',
    '鼓掌': 'emo_13',
    '高兴': 'emo_14',
    '鄙视': 'emo_15',
    '冒汗': 'emo_16',
    '问号': 'emo_17',
    '抠鼻': 'emo_18',
    '我晕': 'emo_19',
    '色色': 'emo_20',
};

var faceon = function () {
    $(".wl_faces_main").empty();
    var str = ""
    str += '<ul>';
    str += '<li><img title="你好" src="/upload/emoji/emo_01.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="偷笑" src="/upload/emoji/emo_02.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="可怜" src="/upload/emoji/emo_03.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="惊讶" src="/upload/emoji/emo_04.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="敲你" src="/upload/emoji/emo_05.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="帅气" src="/upload/emoji/emo_06.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="害羞" src="/upload/emoji/emo_07.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="阴险" src="/upload/emoji/emo_08.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="拜拜" src="/upload/emoji/emo_09.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="大哭" src="/upload/emoji/emo_10.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="亲亲" src="/upload/emoji/emo_11.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="发怒" src="/upload/emoji/emo_12.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="鼓掌" src="/upload/emoji/emo_13.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="高兴" src="/upload/emoji/emo_14.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="鄙视" src="/upload/emoji/emo_15.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="冒汗" src="/upload/emoji/emo_16.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="问号" src="/upload/emoji/emo_17.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="抠鼻" src="/upload/emoji/emo_18.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="我晕" src="/upload/emoji/emo_19.gif" onclick="emoj(this)"/></li>';
    str += '<li><img title="色色" src="/upload/emoji/emo_20.gif" onclick="emoj(this)"/></li>';
    str += "</ul>";
    $(".wl_faces_main").append(str);
    $(".tool_box").toggle();
    var e = window.event || arguments.callee.caller.arguments[0];
    e.stopPropagation();
};

$('body').click(function () {
    $(".tool_box").hide();
});


var emoj = function (obj) {
    var a = $(obj).attr("title");
    var str = $("#text_in").val();
    var reg = new RegExp('<', "g")
    str = str.replace(reg, '&lt;');

    var reg2 = new RegExp('>', "g")

    str = str.replace(reg2, '&gt;');
    var b = "";
    b += str + " face[" + a + "]";
    $("#text_in").val(b);
    $(".tool_box").hide()

}

// 访客手机端：渲染一条图片消息到会话列表
function appendVisitorImageMsg(resData) {
    var msg = '<img src="' + resData + '"  >';
    var myDate = new Date();
    var time = myDate.toLocaleTimeString();
    var str = '';

    var dat = resData.replace(/<[^>]+>/g, "");

    var ct = '';
    if (isValidHttpUrl(dat)) {
        ct = "<pre>" + resData + "</pre><button  data-url=\"" + dat + "\" class=\"copy_url\">澶嶅埗</button>";
    } else {
        ct = "<pre>" + resData + "</pre>";
    }

    str += '<li class="chatmsg"><div class="showtime">' + time + '</div>';
    str += '<div style="float: right;"><img  class="my-circle cu_pic" src="' + pic + '" ></div>';
    str += "<div class='outer-right'><div class='customer'>";
    str += ct;
    str += "</div></div>";
    str += "</li>";
    $(".conversation").append(str);
    var div = document.getElementById("wrap");

    div.scrollTop = div.scrollHeight;
    setTimeout(function () {
        $('.chatmsg').css({
            height: 'auto'
        });
    }, 0)
}

// 访客手机端：渲染一条视频消息到会话列表
function appendVisitorVideoMsg(url) {
    var myDate = new Date();
    var time = myDate.getHours() + ":" + myDate.getMinutes();
    var str = '';
    str += '<li class="chatmsg"><div class="showtime">' + time + '</div>';
    str += '<div style="float: right;"><img  class="my-circle cu_pic" src="' + pic + '" ></div>';
    str += "<div class='outer-right'><div class='customer'>";
    str += "<pre>";
    str += "<div><video src='" + url + "' controls style='max-width: 300px;max-height: 200px;'></video></div>";
    str += "</pre>";
    str += "</div></div>";
    str += "</li>";

    $(".conversation").append(str);
    var div = document.getElementById("wrap");
    div.scrollTop = div.scrollHeight;
    setTimeout(function () {
        $('.chatmsg').css({
            height: 'auto'
        });
    }, 0)
}

// 图片上传（支持多选）
function put(input) {
    MultiSend.pick(input, {
        type: 'image',
        send: function (file, onOk, onFail) {
            MultiSend.uploadFile(file, {
                url: YMWL_ROOT_URL + '/admin/event/upload',
                fieldName: 'upload',
                data: {
                    visiter_id: visiter_id,
                    business_id: business_id,
                    avatar: pic,
                    record: record,
                    service_id: service_id
                },
                onOk: function (res) {
                    appendVisitorImageMsg(res.data);
                    onOk();
                },
                onFail: function (msg) {
                    onFail(msg);
                }
            });
        }
    });
}

// 视频上传（支持多选）
function putvideo(input) {
    MultiSend.pick(input, {
        type: 'video',
        send: function (file, onOk, onFail) {
            MultiSend.uploadFile(file, {
                url: YMWL_ROOT_URL + '/admin/event/uploadvideo',
                fieldName: 'video',
                data: {
                    visiter_id: visiter_id,
                    business_id: business_id,
                    avatar: pic,
                    record: record,
                    service_id: service_id
                },
                onOk: function (res) {
                    appendVisitorVideoMsg(res.data);
                    onOk();
                },
                onFail: function (msg) {
                    onFail(msg);
                }
            });
        }
    });
}

// 文件上传
function putfile() {

    var value = $('input[name="folder"]').val();
    var sarr = value.split('\\');
    var name = sarr[sarr.length - 1];

    var arr = value.split(".");
    var debugs = arr[1].toLowerCase();
    if (debugs == "js" || debugs == "css" || debugs == "html" || debugs == "php") {
        layer.msg("不支持该格式的文件", {icon: 2});
    } else {


        var myDate = new Date();
        var time = myDate.getHours() + ":" + myDate.getMinutes();

        $("#file").ajaxSubmit({
            url: YMWL_ROOT_URL + '/admin/event/uploadfile',
            type: 'post',
            dataType: 'json',
            data: {
                visiter_id: visiter_id,
                business_id: business_id,
                avatar: pic,
                record: record,
                service_id: service_id
            },
            success: function (res) {
                if (res.code == 0) {
                    var str = '';
                    str += '<li class="chatmsg"><div class="showtime">' + time + '</div>';
                    str += '<div style="float: right;"><img  class="my-circle cu_pic" src="' + pic + '" ></div>';
                    str += "<div class='outer-right'><div class='customer'>";
                    str += "<pre>";
                    str += "<div><a href='" + res.data + "' style='display: inline-block;text-align: center;min-width: 70px;text-decoration: none;' download='" + name + "'><i class='layui-icon' style='font-size: 60px;'>&#xe61e;</i><br>" + name + "</a></div>";
                    str += "</pre>";
                    str += "</div></div>";
                    str += "</li>";

                    $(".conversation").append(str);
                    var div = document.getElementById("wrap");
                    div.scrollTop = div.scrollHeight;
                    var msg = "<div style='height:80px'><a href='" + res.data + "' style='display: inline-block;text-align: center;min-width: 70px;text-decoration: none;' download='" + name + "'><i class='layui-icon' style='font-size: 60px;'>&#xe61e;</i><br>" + name + "</a></div>";
                    var se = $('#services').text();

                    if ($.cookie('services')) {
                        var sid = $.cookie('services');
                    }


                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            }
        });

    }
}


function getbig(obj) {
    var text = $(obj).attr('src');
    layer.open({
        type: 1,
        title: false,
        closeBtn: 1,
        area: '70%',
        shadeClose: true,
        content: "<img src='" + text + "' width='100%' height='100%'>"
    });
}

function showBigImg(nWidth, nHeight, text) {

    var maxwidth = window.innerWidth;
    var maxheight = window.innerHeight;
    var size;
    if ((nHeight > maxheight - 10) || (nWidth > maxwidth - 10)) {
        var widths, heights;
        widths = maxwidth - 30;
        heights = widths * nHeight / nWidth;
        if (heights > maxheight) {
            heights = maxheight - 60;
            widths = heights * nWidth / nHeight;
        }
        size = [widths + 'px', heights + 'px'];
    } else {
        size = [nWidth + 'px', nHeight + 'px'];
    }
    layer.open({
        type: 1,
        title: false,
        closeBtn: 1,
        area: size,
        skin: 'layui-layer-nobg', //没有背景色
        shadeClose: true,
        content: "<img src='" + text + "' style='width:100%;height:100%;'>"
    });
}

$(document).on('click', '.outer-left .service img,.outer-right .customer img', function (e) {
    var that = this;
    var img = new Image();
    img.src = this.src
    // 如果图片被缓存，则直接返回缓存数据
    if (img.complete) {
        var nWidth = img.width;
        var nHeight = img.height;
        if (this.width < nWidth || this.height < nHeight) {
            e.preventDefault();
            showBigImg(nWidth, nHeight, img.src);
        }
    } else {
        img.onload = function () {
            var nWidth = img.width;
            var nHeight = img.height;
            if (that.width < nWidth || that.height < nHeight) {
                e.preventDefault();
                showBigImg(nWidth, nHeight, img.src);
            }
        }
    }
});


function getdata() {
    var showtime = "";
    var curentdata = new Date();
    var time = curentdata.toLocaleDateString();
    var cid = $.cookie("cid");
    if (cid === undefined) {
        cid = '';
    }
    $.ajax({
        url: YMWL_ROOT_URL + "/admin/event/chatdata",
        type: "post",
        data: {hid: cid, vid: visiter_id, service_id: service_id, business_id: business_id},
        beforeSend: function () {
        },
        success: function (res) {
            //添加 最近的 聊天 记录
            var str = '';
            var vaule = visiter;

            if (res.code == 0) {

                $.each(res.data, function (k, v) {

                    if (getdata.puttime) {

                        if ((v.timestamp - getdata.puttime) > 60) {
                            var myDate = new Date(v.timestamp * 1000);
                            var puttime = myDate.toLocaleDateString();
                            if (puttime == time) {
                                showtime = myDate.getHours() + ":" + myDate.getMinutes();

                            } else {
                                showtime = myDate.getFullYear() + "-" + (myDate.getMonth() + 1) + "-" + myDate.getDate() + " " + myDate.getHours() + ":" + myDate.getMinutes();
                            }

                        } else {
                            showtime = "";
                        }

                    } else {

                        var myDate = new Date(v.timestamp * 1000);
                        var puttime = myDate.toLocaleDateString();
                        if (puttime == time) {
                            showtime = myDate.getHours() + ":" + myDate.getMinutes();
                        } else {
                            showtime = myDate.getFullYear() + "-" + (myDate.getMonth() + 1) + "-" + myDate.getDate() + " " + myDate.getHours() + ":" + myDate.getMinutes();
                        }

                    }

                    getdata.puttime = v.timestamp;

                    if (v.content.indexOf('target="_blank') > -1) {
                        v.content = v.content.replace(/alt="">/g, 'alt=""></a>')
                    }
                    var  dat  =   v.content.replace(/<[^>]+>/g,"");
                    var ct='';
                    if(isValidHttpUrl(dat)){
                        ct ="<pre>" +  v.content + "</pre><button data-url=\""+dat+"\" class=\"copy_url\">复制</button>";
                    }else{
                        ct ="<pre>" +  v.content + "</pre>";
                    }

                    if (v.direction == 'to_service') {

                        str += '<li class="chatmsg"><div class="showtime">' + showtime + '</div>';
                        str += '<div class="" style="float: right;"><img class="my-circle" src="' + v.avatar + '" ></div>';
                        str += "<div class='outer-right'><div class='customer'>";
                        str += ct;
                        str += "</div></div>";
                        str += "</li>";

                    } else {


                        str += '<li class="chatmsg" id="xiaox_' + v.cid + '"><div class="showtime">' + showtime + '</div><div style="position: absolute;left:3px;">';
                        str += '<img  class="my-circle  se_pic" src="' + v.avatar + '" ></div>';
                        str += "<div class='outer-left'><div class='service'>";
                        str += ct;
                        str += "</div></div>";
                        str += "</li>";

                    }
                });
                $("img[src*='upload/images']").parent().parent('.customer').css({
                    padding: '0', borderRadius: '0'
                });
                $("img[src*='upload/images']").parent().parent('.service').css({
                    padding: '0', borderRadius: '0'
                });
                setTimeout(function () {
                    $('.chatmsg').css({
                        height: 'auto'
                    });
                }, 0)
                // $(".customer").children('pre').children('img').addClass('chat-img');
                var div = document.getElementById("wrap");
                if ($.cookie("cid") == "") {

                    $(".conversation").append(str);
                    if (div) {
                        $("img").load(function () {
                            div.scrollTop = div.scrollHeight;
                        });
                    }
                } else {

                    $(".conversation").prepend(str);
                    if (res.length <= 2) {
                        $("#top_div").remove();
                        $(".conversation").prepend("<div id='top_div' class='showtime'>已没有数据</div>");
                        if (div) {
                            div.scrollTop = 0;
                        }
                    } else {
                        if (div) {
                            div.scrollTop = div.scrollHeight / 4.2;
                        }
                    }
                }
                if (res.length > 0) {
                    $.cookie("cid", data[0]['cid']);
                    $(".chatmsg_notice").remove();
                }
            }

        }
    });
}

// 通知 客服
var init = function () {

    getquestion(business_id);
    $.cookie("cid", "");
    wolive_connect();

    $.ajax({
        url: YMWL_ROOT_URL + "/admin/event/notice",
        type: 'post',
        data: {
            visiter_id: visiter_id,
            visiter_name: visiter,
            business_id: business_id,
            from_url: record,
            avatar: pic,
            groupid: cid,
            special: special
        },
        dataType: 'json',
        async: false,
        success: function (res) {
            if (res.code == 0) {
                data = res.data;
                $("#img_head").attr('src', data.avatar);
                $("#services").text(data.nick_name);
                data.visiter_id = visiter_id;
                console.log('触发postMessage发送问候语');
                window.parent.postMessage({type: 'greeting', data: data}, '*');


                var  dat  =  data.content.replace(/<[^>]+>/g,"");
                var ct='';
                if(isValidHttpUrl(dat)){
                    ct ="<pre>" + data.content + "</pre><button data-url=\""+dat+"\" class=\"copy_url\">复制</button>";
                }else{
                    ct ="<pre>" + data.content + "</pre>";
                }

                msg = '';
                msg += '<li class="chatmsg_no"><div style="position: absolute;top:2px;left:3px;">';
                msg += '<img  class="my-circle" src="' + data.avatar + '" ></div>';
                msg += "<div class='outer-left'><div class='service'>";
                msg += ct;
                msg += "</div></div>";
                msg += "</li>";
                $(".conversation").append(msg);
                var div = document.getElementById("wrap");
                div.scrollTop = div.scrollHeight;
                setTimeout(function () {
                    $('.chatmsg').css({
                        height: 'auto'
                    });
                }, 0)
                $.cookie("services", data.service_id);
                service_id = data.service_id;
            } else if (res.code == 1) {
                layer.msg(res.msg, {icon: 2});
                if (res.offline_greeting) {
                    var og = '';
                    og += '<li class="chatmsg_notice"><div style="position: absolute;left:3px;">';
                    og += '<img  class="my-circle" src="' + YMWL_ROOT_URL + '/assets/images/index/workerman_logo.png" ></div>';
                    og += "<div class='outer-left'><div class='service'>";
                    og += "<pre>" + res.offline_greeting + "</pre>";
                    og += "</div></div></li>";
                    $(".conversation").append(og);
                }
            } else if (res.code == 2) {
                $("#img_head").attr("src", "/assets/images/index/workerman_logo.png");
                $("#services").text("");
                var num = (typeof res.data !== 'undefined' && res.data !== null) ? res.data : getnums(business_id);
                var tipText = '';
                if (res.queue_tip_show == 1) {
                    tipText = res.queue_tip_text || ("通知 ： 现在还有" + num + " 人在排队，请等待 ....");
                } else if (res.offline_greeting) {
                    tipText = res.offline_greeting;
                }
                if (tipText) {
                    var msg = '';
                    msg += '<li class="chatmsg_notice"><div style="position: absolute;left:3px;">';
                    msg += '<img  class="my-circle" src="' + YMWL_ROOT_URL + '/assets/images/index/workerman_logo.png" ></div>';
                    msg += "<div class='outer-left'><div class='service'>";
                    msg += "<pre>" + tipText + "</pre>";
                    msg += "</div></div>";
                    msg += "</li>";
                    $(".conversation").append(msg);
                }
                $.cookie("services", '');

            } else if (res.code == 3) {
                layer.msg(res.msg, {
                    icon: 2, end: function () {
                        window.location.href = url + "/index/message?business_id=" + business_id;
                    }
                });
                $.cookie("services", '');
            } else if (res.code == 4) {
                var data = res.data;
                $("#img_head").attr('src', data.avatar);
                $("#services").text(data.nick_name);
                $("#img_head").addClass("icon_gray");
                var greeting = res.offline_greeting || (data && data.content) || '';
                if (greeting) {
                    var gmsg = '';
                    gmsg += '<li class="chatmsg_notice"><div style="position: absolute;left:3px;">';
                    gmsg += '<img  class="my-circle se_pic" src="' + (data.avatar || YMWL_ROOT_URL + '/assets/images/index/workerman_logo.png') + '" ></div>';
                    gmsg += "<div class='outer-left'><div class='service'>";
                    gmsg += "<pre>" + greeting + "</pre>";
                    gmsg += "</div></div></li>";
                    $(".conversation").append(gmsg);
                }
                layer.open({
                    title: '提示框',
                    area: ['300px', '180px'],
                    content: '该客服离线中，是否转接其他客服？',
                    btn: ['是', '否'],
                    yes: function () {
                        $.ajax({
                            url: YMWL_ROOT_URL + '/admin/event/getchangekefu',
                            type: 'post',
                            data: {visiter_id: visiter_id, business_id: business_id},
                            success: function (res) {
                                if (res.code == 0) {

                                    layer.msg('转接中....', {
                                        icon: 3, end: function () {
                                            location.reload();
                                        }
                                    })

                                }
                            }
                        });
                    },
                    btn2: function () {
                        layer.close();
                    }
                })

            }
            getdata();
            // 页面加载完成后，标记已有消息为已读
            setTimeout(function() { markAsRead(); }, 1000);
        }
    });

    $.cookie("itime", "");
}
window.onload = init();

function getquestion(business_id) {
    $.ajax({
        url: YMWL_ROOT_URL + '/admin/event/getquestion',
        type: 'post',
        data: {business_id: business_id},
        success: function (res) {
            if (res.code == 0) {
                var logo = res.logo;
                if (logo == undefined || logo == '' || logo == null) {
                    logo = YMWL_ROOT_URL + '/assets/images/index/workerman_logo.png';
                }
                var str = '';
                str += '<li class="chatmsg"><div style="position: absolute;top:2px;left:3px;">';
                str += '<img  class="my-circle" src="' + logo + '" ></div>';
                str += "<div class='outer-left'><div class='service'>";
                str += "<pre>";

                str += '<p style="font-weight:bold;">我猜你想问的：</p>'
                var num = '';

                $.each(res.data, function (k, v) {
                    var a = JSON.stringify(v);
                    if (num) {
                        num = num + 1;
                    } else {
                        num = 1
                    }
                    str += '<div class="question" onclick="getanswer(' + v.qid + ',`' + v.question + '`)"><span>' + num + '. </span> ' + v.question + '</div>';
                    if (v.keyword != '') {
                        $("#question_key_list").append('<div class="keyword-item swiper-slide" onclick="getanswer(' + v.qid + ',`' + v.question + '`)">' + v.keyword + '</div>');
                    }
                });

                str += "</pre></div></div>";
                str += "</li>";

                $(".conversation").append(str);

                if ($('.keyword-item').length > 0) {
                    $('.keyword').show();
                    $('#log').css({
                        paddingBottom: '50px',
                    });
                }
            }
        }
    });

}

function getanswer(id, question) {
    var str = '';
    str += '<li class="chatmsg"><div class="showtime"></div>';
    str += '<div  style="float: right;"><img  class="my-circle" src="' + pic + '" ></div>';
    str += "<div class='outer-right'><div class='customer'>";
    str += "<pre>" + question + "</pre>";
    str += "</div></div>";
    str += "</li>";
    $(".conversation").append(str);
    $.ajax({
        url: YMWL_ROOT_URL + '/admin/event/getanswer',
        type: 'post',
        data: {qid: id, service_id: service_id, visiter_id: visiter_id},
        success: function (res) {

            if (res.code == 0) {

            }
        }
    });
}

// 获取排队的数量
function getnums(id) {
    var value = "";
    $.ajax({
        url: YMWL_ROOT_URL + "/admin/event/getwaitnum",
        type: "post",
        async: false,
        data: {business_id: id, groupid: cid},
        success: function (res) {
            value = res;
        }
    });
    return value;
}

//发送消息
var send = function () {
    //获取 游客id
    var msg = $("#text_in").val();
    var reg = new RegExp('<', "g")
    var msg2 = msg.replace(reg, '&lt;');

    var reg2 = new RegExp('>', "g")
    msg2 = msg2.replace(reg2, '&gt;');
    /*msg2 =msg2.replace('http://','');
  msg2 =msg2.replace('https://','');

  msg2=msg2.replace(/[a-z]+[.]{1}[a-z\d\-]+[.]{1}[a-z\d]*[\/]*[A-Za-z\d]*[\/]*[A-Za-z\d]*[\/]*[A-Za-z\d]*[\/]*[A-Za-z\d]/g,function (i) {

    return 'http://'+i;
 });*/
    msg2 = msg2.replace(/(https?|http|ftp|file):\/\/[-A-Za-z0-9+&@#/%?=~_|!:,.;]+[-A-Za-z0-9+&@#/%=~_|]/g, function (i) {

        // a=i.replace('http://','');
        return '<a href="' + i + '" target="_blank">' + i + '</a>';
    });


    if (msg2.indexOf("face[") != -1) {

        msg2 = msg2.replace(/face\[([^\s\[\]]+?)\]/g, function (i) {
            var a = i.replace(/^face/g, "");
            a = a.replace('[', '');
            a = a.replace(']', '');
            return '<img src="' + YMWL_ROOT_URL + '/upload/emoji/' + e[a] + '.gif" />'
        });

    }


    if (msg == '' || $.cookie("service") == '') {
        layer.msg('请输入信息');
    } else {
        var time;

        if ($.cookie("itime") == "") {
            var myDate = new Date();
            var minutes = myDate.getMinutes();
            if (minutes < 10) {
                minutes = '0' + minutes.toString();
            }
            time = myDate.getHours() + ":" + minutes;
            var timestamp = Date.parse(new Date());
            $.cookie("itime", timestamp / 1000);

        } else {

            var timestamp = Date.parse(new Date());
            var lasttime = $.cookie("itime");
            if ((timestamp / 1000 - lasttime) > 30) {
                var myDate = new Date(timestamp);
                var minutes = myDate.getMinutes();
                if (minutes < 10) {
                    minutes = '0' + minutes.toString();
                }
                time = myDate.getHours() + ":" + minutes;
            } else {
                time = "";
            }

            $.cookie("itime", timestamp / 1000);
        }

        var str = '';
        var  dat  = msg2.replace(/<[^>]+>/g,"");
        var ct='';
        if(isValidHttpUrl(dat)){
            ct ="<pre>" + msg2+ "</pre><button  data-url=\""+dat+"\" class=\"copy_url\">复制</button>";
        }else{
            ct ="<pre>" + msg2 + "</pre>";
        }

        str += '<li class="chatmsg"><div class="showtime">' + time + '</div>';
        str += '<div class="" style="float: right;"><img  class="my-circle cu_pic" src="' + pic + '" ></div>';
        str += "<div class='outer-right'><div class='customer'>";
        str += ct;
        str += "</div></div>";
        str += "</li>";

        $(".conversation").append(str);
        $("#text_in").val('');
        var div = document.getElementById("wrap");
        div.scrollTop = div.scrollHeight;
        $("img[src*='upload/images']").parent().parent('.customer').css({
            padding: '0', borderRadius: '0'
        });
        $("img[src*='upload/images']").parent().parent('.service').css({
            padding: '0', borderRadius: '0'
        });
        setTimeout(function () {
            $('.chatmsg').css({
                height: 'auto'
            });
        }, 0)
        $.ajax({
            url: YMWL_ROOT_URL + "/admin/event/chat",
            type: "post",
            data: {
                visiter_id: visiter_id,
                content: msg2,
                business_id: business_id,
                avatar: pic,
                record: record,
                service_id: service_id
            },
            dataType: 'json',
            success: function (res) {
                str = '';
                if (res.code == 100) {
                    if ($.cookie('state') != 'off') {
                        document.getElementById("chat-message-audio").play();
                    }
                    var logo = res.logo;
                    str += '<li class="chatmsg"><div style="position: absolute;top:2px;left:3px;">';
                    str += '<img  class="my-circle" src="' + logo + '" ></div>';
                    str += "<div class='outer-left'><div class='service'>";
                    str += "<pre>";
                    str += '<p style="font-weight:bold;">我猜你想问的：</p>'
                    var num = '';
                    $.each(res.data, function (k, v) {
                        var a = JSON.stringify(v);
                        if (num) {
                            num = num + 1;
                        } else {
                            num = 1
                        }
                        str += '<div class="question" onclick="getanswer(' + v.qid + ',`' + v.question + '`)"><span>' + num + '. </span> ' + v.question + '</div>';
                    });
                    str += "</pre></div></div>";
                    str += "</li>";
                    $(".conversation").append(str);
                } else if (res.code == 101) {
                    if ($.cookie('state') != 'off') {
                        document.getElementById("chat-message-audio").play();
                    }

                    var  dat  =  res.data.replace(/<[^>]+>/g,"");
                    var ct='';
                    if(isValidHttpUrl(dat)){
                        ct ="<pre>" + res.data + "</pre><button data-url=\""+dat+"\" class=\"copy_url\">复制</button>";
                    }else{
                        ct ="<pre>" + res.data + "</pre>";
                    }

                    str += '<li class="chatmsg"><div class="showtime">' + time + '</div><div style="position: absolute;left:3px;">';
                    str += '<img  class="my-circle  se_pic" src="' + res.logo + '" ></div>';
                    str += "<div class='outer-left'><div class='service'>";
                    str += ct;
                    str += "</div></div>";
                    str += "</li>";
                    $(".conversation").append(str);
                } else if (res.code == 102) {
                    //                返回在线客服列表选择
                    if ($.cookie('state') != 'off') {
                        document.getElementById("chat-message-audio").play();
                    }
                    str += '<li class="chatmsg"><div style="position: absolute;top:2px;left:3px;">';
                    str += '<img  class="my-circle" src="' + res.logo + '" ></div>';
                    str += "<div class='outer-left'><div class='service'>";
                    str += "<pre>";
                    str += '<p style="font-weight:bold;">点击选择如下客服进行对话：</p>'
                    var num = '';
                    $.each(res.data, function (k, v) {
                        var a = JSON.stringify(v);
                        if (num) {
                            num = num + 1;
                        } else {
                            num = 1
                        }
                        state = v.state == 'online' ? '在线' : '离线';
                        str += '<div class="question toserver" onclick="toService(' + v.service_id + ')"><span>' + num + '. </span> ' + v.nick_name + '[' + state + ']' + '</div>';
                    });

                    str += "</pre></div></div>";
                    str += "</li>";
                    $(".conversation").append(str);
                } else if (res.code == 1) {
                    layer.msg(res.msg, {icon: 2});
                }
                div.scrollTop = div.scrollHeight;
            }
        });
    }
}


document.getElementById("text_in").onkeydown = function (e) {
    e = e || window.event;
    if (e.ctrlKey && e.keyCode == 13) {
        if ($('#text_in').val() == "" || $.cookie("service") == '') {
            layer.msg('请输入信息');
        } else {
            send();
        }
    }
}

var loginout = function () {

    delCookie("visiter");

    $.ajax({
        url: YMWL_ROOT_URL + "/admin/event/qdelete",
        type: "post",
        data: {channel: channel},
        success: function (res) {


            if (res) {
                window.history.go(-1);
            } else {

                window.history.go(-1);
            }
        }
    });
}

function delCookie(name) {
    var date = new Date();
    date.setTime(date.getTime() - 10000);
    document.cookie = name + "=a; expires=" + date.toGMTString();
}

function isWeiXin() {
    var ua = window.navigator.userAgent.toLowerCase();
    if (ua.match(/MicroMessenger/i) == 'micromessenger') {
        return true;
    } else {
        return false;
    }
}

if (isWeiXin()) {
    $('.fanhui').hide();
}
$(document).on('click', '#followbtn', function () {
    $.ajax({
        url: "/index/index/qrcode.html",
        dataType: 'json',
        data: {"visiter_id": visiter_id, 'business_id': business_id},
        type: "POST",   //请求方式
        success: function (res) {
            if (res.code > 0) {
                layer.open({
                    title: '绑定公众号,享专属服务',
                    type: 1,
                    content: '<img style="width: 260px;height: 260px;" src="' + res.data + '"/>',
                });
            } else {
                lay.alert(res.msg);
            }
        }
    });
});

//转客服
function toService(service_id) {
    var initdata = {
        service_id: service_id,
        visiter_id: visiter_id,
        business_id: business_id
    };

    $.ajax({
        url: YMWL_ROOT_URL + '/admin/event/getswitch',
        type: 'post',
        data: initdata,
        dataType: 'json',
        success: function (res) {
            if (res.code == 1) {
                layer.msg(res.msg, {icon: 2});
            }
        }
    });
}

$(document).on('click','.copy_url',function(e) {
    let url  =$(this).data("url");
    copyText(url);
    layer.msg("复制成功");

})

// 标记客服消息为已读
function markAsRead() {
    if (typeof visiter_id === 'undefined' || !visiter_id) {
        console.log('markAsRead: visiter_id not defined');
        return;
    }
    console.log('markAsRead: calling getwatchVisitor for visiter_id=' + visiter_id + ', business_id=' + business_id);
    $.ajax({
        url: YMWL_ROOT_URL + "/admin/event/getwatchVisitor",
        type: "post",
        data: { visiter_id: visiter_id, business_id: business_id },
        dataType: 'json',
        success: function(res) {
            console.log('markAsRead: success', res);
        },
        error: function(xhr, status, error) {
            console.log('markAsRead: failed', status, error, xhr.responseText);
        }
    });
}

// 使用 MutationObserver 监听消息显示，自动标记已读（兼容微信浏览器）
var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        mutation.addedNodes.forEach(function(node) {
            if (node.nodeType === 1) {
                var $node = $(node);
                if ($node.hasClass('chatmsg') && ($node.find('.service').length > 0 || $node.find('.se_pic').length > 0)) {
                    markAsRead();
                }
            }
        });
    });
});
observer.observe(document.getElementById('wrap') || document.querySelector('.conversation'), { childList: true, subtree: true });

function copyText(text) {
    var oInput = document.createElement('input');
    oInput.value = text;
    document.body.appendChild(oInput);
    oInput.select();
    document.execCommand("Copy");
    oInput.className = 'oInput';
    oInput.style.display = 'none';
}

function isValidHttpUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (err) {
        return false;
    }

}
