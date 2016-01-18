var operate = function(){

    var openShare = function(params){
        wx.ready(function () {
            // 分享到朋友圈
            wx.onMenuShareTimeline({
                title: params.title, // 分享标题
                link: params.link, // 分享链接
                imgUrl: params.imgUrl, // 分享图标
                success: function () {
                },
                cancel: function () {
                }
            });

            // 分享给朋友
            wx.onMenuShareAppMessage({
                title: params.title, // 分享标题
                desc: params.desc, // 分享描述
                link: params.link, // 分享链接
                imgUrl: params.imgUrl, // 分享图标
                type: "link", // 分享类型,music、video或link，不填默认为link
                dataUrl: "", // 如果type是music或video，则要提供数据链接，默认为空
                success: function () {
                },
                cancel: function () {
                }
            });
        });
    };
    var closeShare = function(params){

        wx.ready(function () {
            wx.hideOptionMenu();
        });
    };

    return{
        initOpenShare: function(params){
            openShare(params);
        },
        initCloseShare: function(params){
            closeShare(params);
        }
    }
}();