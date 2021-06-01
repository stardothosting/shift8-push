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
        alert(JSON.stringify(data)); 
    });

    // Manually import webinars
    jQuery(document).on( 'click', '#shift8-push-import', function(e) {
        jQuery(".shift8-push-spinner").show();
        e.preventDefault();
        var button = jQuery(this);
        var url = button.attr('href');
        jQuery.ajax({
            url: url,
            dataType: 'json',
            data: {
                'action': 'shift8_push_push',
                'type': 'import'
            },
            success:function(data) {
                // This outputs the result of the ajax request
                jQuery('.shift8-push-response').html('Zoom import successful. Total webinars polled : ' + data.total_records + ' Total new webinars imported : ' + data.webinars_imported).fadeIn();
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


function Shift8ZoomCopyToClipboard(containerid) {
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
