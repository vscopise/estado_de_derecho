jQuery(document).ready(function($) {
    var $affectedElements = $("p");
    
    $affectedElements.each( function(){
        var $this = $(this);
        $this.data("orig-size", $this.css("font-size") );
    });
    
    $(".btn-increase").click(function(){
        changeFontSize(1);
    })

    $(".btn-decrease").click(function(){
        changeFontSize(-1);
    })

    $(".btn-orig").click(function(){
        $affectedElements.each( function(){
            var $this = $(this);
            $this.css( "font-size" , $this.data("orig-size") );
        });
    });

    function changeFontSize(direction){
        $affectedElements.each( function(){
            var $this = $(this);
            $this.css( "font-size" , parseInt($this.css("font-size"))+direction );
        });
    };

});