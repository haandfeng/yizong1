/**
 * �˲����Ҫ��������UC��QQ�������������
 * ���津��΢�ŷ�������Ȧ���͸����ѵĹ���
 */
'use strict';
var UA = navigator.appVersion;
 debugger;
/**
 * �Ƿ��� UC �����
 */
var uc = UA.split('UCBrowser/').length > 1 ? 1 : 0;
 
/**
 * �ж� qq �����
 * Ȼ��qq������ָߵͰ汾
 * 2 ����߰汾
 * 1 ����Ͱ汾
 */
var qq = UA.split('MQQBrowser/').length > 1 ? 2 : 0;
 
/**
 * �Ƿ���΢��
 */
var wx = /micromessenger/i.test(UA);
 
/**
 * ������汾
 */
var qqVs = qq ? parseFloat(UA.split('MQQBrowser/')[1]) : 0;
var ucVs = uc ? parseFloat(UA.split('UCBrowser/')[1]) : 0;
 
/**
 * ��ȡ����ϵͳ��Ϣ  iPhone(1)  Android(2)
 */
var os = (function () {
    var ua = navigator.userAgent;
 
    if (/iphone|ipod/i.test(ua)) {
        return 1;
    } else if (/android/i.test(ua)) {
        return 2;
    } else {
        return 0;
    }
}());
 
/**
 * qq��������� �Ƿ���غ�����Ӧ��api�ļ�
 */
var qqBridgeLoaded = false;
 
// ��һ��ϸ���汾��ƽ̨�ж�
if ((qq && qqVs < 5.4 && os == 1) || (qq && qqVs < 5.3 && os == 1)) {
    qq = 0;
} else {
    if (qq && qqVs < 5.4 && os == 2) {
        qq = 1;
    } else {
        if (uc && ((ucVs < 10.2 && os == 1) || (ucVs < 9.7 && os == 2))) {
            uc = 0;
        }
    }
}
 
/**
 * qq��������� ���ݲ�ͬ�汾 ���ض�Ӧ��bridge
 * @method loadqqApi
 * @param  {Function} cb �ص�����
 */
function loadqqApi(cb) {
    // qq == 0 
    if (!qq) {
        return cb && cb();
    }
 
    var script = document.createElement('script');
    script.src = (+qq === 1) ? '//3gimg.qq.com/html5/js/qb.js' : '//jsapi.qq.com/get?api=app.share';
 
    /**
     * ��Ҫ�ȼ��ع� qq �� bridge �ű�֮��
     * ��ȥ��ʼ���������
     */
    script.onload = function () {
        cb && cb();
    };
 
    document.body.appendChild(script);
}
 
 
/**
 * UC���������
 * @method ucShare
 */
function ucShare(config) {
    // ['title', 'content', 'url', 'platform', 'disablePlatform', 'source', 'htmlID']
    // ����platform
    // ios: kWeixin || kWeixinFriend;
    // android: WechatFriends || WechatTimeline
    // uc �����ֱ��ʹ�ý�ͼ
 debugger;
    var platform = '';
    var shareInfo = null;
 
    // ָ���˷�������
    if (config.type) {
        if (os == 2) {
            platform = config.type == 1 ? 'WechatTimeline' : 'WechatFriends';
        } else if (os == 1) {
            platform = config.type == 1 ? 'kWeixinFriend' : 'kWeixin';
        }
    }
 
    shareInfo = [config.title, config.desc, config.url, platform, '', '', ''];
 
    // android 
    if (window.ucweb) {
        ucweb.startRequest && ucweb.startRequest('shell.page_share', shareInfo);
        return;
    }
 
    if (window.ucbrowser) {
        ucbrowser.web_share && ucbrowser.web_share.apply(null, shareInfo);
        return;
    }
}
 
 
/**
 * qq �����������
 * @method qqShare
 */
function qqShare(config) {
    var type = config.type;
 debugger;
    //΢�ź��� 1, ΢������Ȧ 8
    type = type ? ((type == 1) ? 8 : 1) : '';
 
    var share = function () {
        var shareInfo = {
            'url': config.url,
            'title': config.title,
            'description': config.desc,
            'img_url': config.img,
            'img_title': config.title,
            'to_app': type,
            'cus_txt': ''
        };
 
        if (window.browser) {
            browser.app && browser.app.share(shareInfo);
        } else if (window.qb) {
            qb.share && qb.share(shareInfo);
        }
    };
 
    if (qqBridgeLoaded) {
        share();
    } else {
        loadqqApi(share);
    }
}
 
/**
 * ���Ⱪ¶�Ľӿں���
 * @method mShare
 * @param  {Object} config ���ö���
 */
function mShare(config) {
    this.config = config;
 debugger;
    this.init = function (type) {
        if (typeof type != 'undefined') this.config.type = type;
 
        try {
            if (uc) {
                ucShare(this.config);
            } else if (qq && !wx) {
                qqShare(this.config);
            }
        } catch (e) {}
    }
}
 
// Ԥ���� qq bridge
loadqqApi(function () {
    qqBridgeLoaded = true;
});
 
if (typeof module === 'object' && module.exports) {
    module.exports = mShare;
} else {
    window.mShare = mShare;
} 