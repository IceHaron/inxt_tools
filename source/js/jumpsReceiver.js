var scr_request_cfg = {
    sourceUrl: 'https://api.eveonline.com/map/Jumps.xml.aspx',
    destinationUrl: 'http://gaminas.inextinctae.ru/wtf/sysactiv/write'
};

var evaRequestor = {
    start: function (sourceUrl, destinationUrl) {

        this.sourceUrl = sourceUrl || scr_request_cfg.sourceUrl;
        this.destinationUrl = destinationUrl || scr_request_cfg.destinationUrl;


        this.request();
    },

    stop: function () {
        if (this.timerId != null) {
            window.clearTimeout(this.timerId);
        }
    },

    request: function () {

        var me = this;
        $.ajax({
            type: 'GET',
            url: this.sourceUrl,
            contentType: "application/xml",
            success: function (response) {
								console.log(response);
                var eveApiNode = response.children[0];
                var currentTime = new Date(eveApiNode.children[0].textContent);
                var cachedUntilTime = new Date(eveApiNode.children[2].textContent);
                var nextRequestTimeout = cachedUntilTime - currentTime + 1000;

                var data = eveApiNode.children[1].children[0];

                var jsonResult = [];

                for (var i = 0; i < data.childElementCount; i++) {
                    var row = data.children[i];

                    jsonResult.push({
                        solarsystemid: row.attributes[0].value,
                        shipjumps: row.attributes[1].value
                    });
                }


                this.timerId = window.setTimeout(function () { me.request(); }, nextRequestTimeout > 0 ? nextRequestTimeout : 1000);

                $.ajax({
                    type: 'POST',
                    url: me.destinationUrl,
                    data: JSON.stringify({ answer: jsonResult }),
                    dataType: 'string',
                    success: function () { console.log('data has been transfered'); },
                    error: function () { },
                    complete: function () { }
                });
            },
						error: function(q, s, e) {
							console.log(s, e);
						}
        });
    }
};