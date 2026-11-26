/**
 * 解析Ajax请求获取的HTML内容
 * @param data
 * @returns {*}
 */
var parse_html = function (data) {
    var pattern = /<body[^>]*>((.|[\n\r])*)<\/body>/im;
    var matches = pattern.exec(data);
    if (matches) {
        return matches[1];
    }
    return data;
};

/**
 * 全局设置AJAX请求，自动添加CSRF令牌
 */
(function() {
    // 保存原始的jQuery.ajax方法
    var originalAjax = $.ajax;
    
    // 不需要添加CSRF令牌的路径
    var csrfExcludePaths = [
        '/admin/ai/',
        'admin/ai/'
    ];
    
    // 检查URL是否在排除列表中
    function shouldExcludeCsrf(url) {
        for (var i = 0; i < csrfExcludePaths.length; i++) {
            if (url.indexOf(csrfExcludePaths[i]) !== -1) {
                return true;
            }
        }
        return false;
    }
    
    // 重写jQuery.ajax方法
    $.ajax = function(options) {
        // 如果是POST/PUT/DELETE请求，自动添加CSRF令牌
        if (options.type && ['POST', 'PUT', 'DELETE'].indexOf(options.type.toUpperCase()) !== -1) {
            // 检查是否在排除列表中
            if (shouldExcludeCsrf(options.url)) {
                // 跳过CSRF令牌添加
            } else {
                // 如果data是对象，添加CSRF令牌
                if (typeof options.data === 'object' && options.data !== null) {
                    options.data.__token__ = window.__CSRF_TOKEN__ || '';
                } else if (typeof options.data === 'string') {
                    // 如果data是字符串，追加CSRF令牌
                    options.data += (options.data ? '&' : '') + '__token__=' + encodeURIComponent(window.__CSRF_TOKEN__ || '');
                } else if (!options.data) {
                    // 如果data为空，创建包含CSRF令牌的对象
                    options.data = {__token__: window.__CSRF_TOKEN__ || ''};
                }
            }
        }
        
        // 调用原始的ajax方法
        return originalAjax.apply(this, arguments);
    };
    
    // 设置全局AJAX请求头
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            // 对于非GET请求，添加CSRF令牌到请求头
            if (settings.type && settings.type.toUpperCase() !== 'GET') {
                // 检查是否在排除列表中
                if (!shouldExcludeCsrf(settings.url)) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', window.__CSRF_TOKEN__ || '');
                }
            }
        }
    });
})();
/**
 * 扩展函数库
 * Created by PhpStorm.
 * @version 2016-07-25 14:46:29
 * @author  cbwfree
 */

/**
 * 解析URL
 * @param {string} url 完整的URL地址
 * @returns {object} 自定义的对象
 */
function parse_url(url) {
    var a = document.createElement('a');
    a.href = url;
    return {
        protocol: a.protocol.replace(':', ''),
        host: a.hostname,
        port: a.port,
        query: a.search,
        params: parse_url_params(a.search),
        file: (a.pathname.match(/\/([^\/?#]+)$/i) || [, ''])[1],
        path: a.pathname.replace(/^([^\/])/, '/$1')
    };
}

/**
 * 解析URL参数
 * @param query
 * @returns {{}}
 */
function parse_url_params(query) {
    var ret = {},
        seg = query.replace(/^\?/, '').split('&'),
        len = seg.length, i = 0, s;
    for (; i < len; i++) {
        if (!seg[i]) {
            continue;
        }
        s = seg[i].split('=');
        ret[s[0]] = s[1];
    }
    return ret;
}

/**
 * 构建URL
 * @param url
 * @param vars
 */
function build_url(url, vars) {
    var parse = parse_url(url),
        query = parse.params || {};
    (typeof vars == "string") ? vars = parse_url_params(vars) : '';
    $.each(vars, function (k, v) {
        query[k] = v;
    });
    return parse.path + "?" + $.param(query);
}

function notify(title, options, callback) {
    // 先检查浏览器是否支持
    if (!window.Notification || document.visibilityState != 'hidden') {
        return;
    }
    var notification;
    // 检查用户曾经是否同意接受通知
    if (Notification.permission === 'granted') {
        notification = new Notification(title, options); // 显示通知

    } else if (Notification.permission === 'default') {
        var promise = Notification.requestPermission();
    }

    if (notification && callback) {
        notification.onclick = function(event) {
            callback(notification, event);
        }
    }
}
if (window.Notification && Notification.permission === 'default') {
    var promise = Notification.requestPermission();
}