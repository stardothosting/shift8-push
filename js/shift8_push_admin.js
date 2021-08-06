jQuery(document).ready(function() {

    // Check & synchronize config of CDN account
    jQuery(document).on( 'click', '#shift8-push-check', function(e) {
        jQuery(".shift8-push-spinner").show();
        e.preventDefault();
        var button = jQuery(this);
        var url = button.attr('href');

        jQuery.ajax({
            url: url,
            dataType: 'json',
            data: {
                'action': 'shift8_push_push',
                'type': 'check'
            },
            success:function(data) {
                // This outputs the result of the ajax request
                jQuery('.shift8-push-response').html('Push connection test successful : ' + data.namespace).fadeIn();
                setTimeout(function(){ jQuery('.shift8-push-response').fadeOut() }, 25000);
                jQuery(".shift8-push-spinner").hide();               
            },
            error: function(errorThrown){
                console.log('Error : ' + JSON.stringify(errorThrown));
                jQuery('.shift8-push-response').html(errorThrown.responseText).fadeIn();
                setTimeout(function(){ jQuery('.shift8-push-response').fadeOut() }, 5000);
                jQuery(".shift8-push-spinner").hide();
            }
        });
    });

    // Manually import webinars
    jQuery(document).on( 'click', '#shift8-push-trigger', function(e) {
        jQuery(".shift8-push-spinner").show();
        e.preventDefault();
        var button = jQuery(this);
        var url = button.attr('href');
        const urlSearchParams = new URLSearchParams(url);
        const params = Object.fromEntries(urlSearchParams.entries());

        jQuery.ajax({
            url: url,
            //dataType: 'json',
            data: {
                'action': 'shift8_push_push',
                'type': 'push',
                'item_id': params.item_id,
            },
            success:function(data) {
                // This outputs the result of the ajax request
                console.log('Response : ' + JSON.stringify(data, null,2));
                jQuery('.shift8-push-response').html('Push successful!').fadeIn();
                setTimeout(function(){ jQuery('.shift8-push-response').fadeOut() }, 25000);
                jQuery(".shift8-push-spinner").hide();               
            },
            error: function(errorThrown){
                console.log('Error : ' + JSON.stringify(errorThrown));
                jQuery('.shift8-push-response').html(errorThrown.responseText).fadeIn();
                setTimeout(function(){ jQuery('.shift8-push-response').fadeOut() }, 5000);
                jQuery(".shift8-push-spinner").hide();
            }
        });
    });
});


function Shift8PushCopyToClipboard(containerid) {
    if (document.selection) { 
        var range = document.body.createTextRange();
        range.moveToElementText(document.getElementById(containerid));
        range.select().createTextRange();
        document.execCommand("copy"); 

    } else if (window.getSelection) {
        var range = document.createRange();
         range.selectNode(document.getElementById(containerid));
         window.getSelection().addRange(range);
         document.execCommand("copy");
         alert("text copied") 
    }
}
