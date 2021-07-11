    jQuery(document).ready(function() {
      setTimeout(function(){
        jQuery(".editor-toolbar a[title='Create Link']").click(function() {
          console.log("kkkkkkkkkkkkkkk");          
            jQuery('#mceu_58 + div>div').prepend('<button onClick="jQuery(this).parent().parent().remove()" type="button" class="media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text">Close media panel</span></span></button>');          
        });
      },1000);
    });