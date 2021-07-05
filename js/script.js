var s = jQuery(".acf-taxonomy-field[data-taxonomy=category]").children().clone();
var v1 = jQuery(".acf-taxonomy-field[data-taxonomy=post_tag]").children().clone();

if(jQuery( '.post_lang_choice' ).val()!='da'){
    var url = window.location.href; 
    var id = url.substring(url.lastIndexOf("post=")+5,url.lastIndexOf("&action"));      
   
    var drName = jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] select").attr('name');
    var dropdw = jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] select").clone();
    jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] input+select").attr('id', 'prevv'); 
    jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] input+select").attr('name',jQuery('#prevv').attr('name').replace('acf','acf_'+jQuery('.post_lang_choice').val()));
    setTimeout(function(){
    jQuery(".acf-taxonomy-field[data-taxonomy=post_tag]").append(dropdw);    
    jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] select").addClass('select2-hidden-accessible');  
    },1000);   
    
  jQuery.ajax({
    url: ajaxurl,
    data: {
        'action':'langs_tags',
        'id':id,
        'all':'all',
        'lang':jQuery('.post_lang_choice').val(),
        'tags' : jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] select#prevv").val()
    },
    success:function(data) {
      var array = JSON.parse(data.slice(0, -1));
      var el ='';
      for (var i in array){
          el+='<option value="'+array[i][0].term_id+'" selected="selected">'+array[i][0].name+'</option>';
      }
       jQuery('#prevv').html(el);
    },  
    error: function(errorThrown){
        console.log(errorThrown);
    }
 });  
}

jQuery( '.post_lang_choice' ).change(function() {  
  var url = window.location.href; 
  var id = url.substring(url.lastIndexOf("post=")+5,url.lastIndexOf("&action"));
    setTimeout(function(){      
       jQuery(".acf-taxonomy-field[data-taxonomy=category]").html(s);
       jQuery('.acf-taxonomy-field[data-taxonomy=category] select').select2({        
         ajax: {
           url: '/wp/wp-admin/admin-ajax.php?_fs_blog_admin=true',
           type:  'POST',
           data: { 
              action: 'acf/fields/taxonomy/query',
              s: '',
              paged: 1,
              field_key: jQuery('.acf-taxonomy-field[data-taxonomy=category] select').attr('id').substring(4),
              nonce: window.ajax_var.nonce,
              post_id: id
            },
           success: function (data) {
               return data;
           },
           error: function () {
               alert("error");
           }
         }
       });       
       jQuery(".acf-taxonomy-field[data-taxonomy=category] select").addClass('select2-hidden-accessible');
       jQuery(".acf-taxonomy-field[data-taxonomy=post_tag]").html(v1);
       jQuery('.acf-taxonomy-field[data-taxonomy=post_tag] select').select2({
         ajax: {
           url: '/wp/wp-admin/admin-ajax.php?_fs_blog_admin=true',
           type:  'POST',
           data: { 
              action: 'acf/fields/taxonomy/query',
              s: '',
              paged: 1,
              field_key: jQuery('.acf-taxonomy-field[data-taxonomy=post_tag] select').attr('id').substring(4),
              nonce: window.ajax_var.nonce,
              post_id: id
            },
           success: function (response) {             
               return response;
           },
           error: function () {
               alert("error");
           }
         }
       });   
       jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] select").addClass('select2-hidden-accessible');
       jQuery('.select2-container').addClass('-acf');  
       if(jQuery('.post_lang_choice').val()!='da'){     
       var drName = jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] select").attr('name');
       var dropdw = jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] select").clone();
       jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] input+select").attr('id', 'prevv'); 
       jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] input+select").attr('name',jQuery('#prevv').attr('name').replace('acf','acf_'+jQuery('.post_lang_choice').val()));
       jQuery(".acf-taxonomy-field[data-taxonomy=post_tag]").append(dropdw);   
       
       jQuery.ajax({
        url: ajaxurl,
        data: {
            'action':'langs_tags',
            'id':id,
            'all':'all',
            'lang':jQuery('.post_lang_choice').val(),
            'tags' : jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] select#prevv").val()
        },
        success:function(data) {
          var array = JSON.parse(data.slice(0, -1));
          var el ='';
          for (var i in array){
              el+='<option value="'+array[i][0].term_id+'" selected="selected">'+array[i][0].name+'</option>';
          }
           jQuery('#prevv').html(el);
        },  
        error: function(errorThrown){
            console.log(errorThrown);
        }
       });         
      }
      },3000);   
   });

   jQuery('#submitpost #major-publishing-actions #publishing-action input[type=submit]').click(function(){
    if(jQuery('.post_lang_choice').val()!='da'){
      var url = window.location.href; 
      var id = url.substring(url.lastIndexOf("post=")+5,url.lastIndexOf("&action"));      
      
      jQuery.ajax({
          url: ajaxurl,
          data: {
              'action':'langs_tags',
              'id':id,
              'lang':jQuery('.post_lang_choice').val(),
              'tags' : jQuery(".acf-taxonomy-field[data-taxonomy=post_tag] select#prevv").val()
          },
          success:function(data) {
              console.log(data);
          },  
          error: function(errorThrown){
              console.log(errorThrown);
          }
      });     
     } 
   })