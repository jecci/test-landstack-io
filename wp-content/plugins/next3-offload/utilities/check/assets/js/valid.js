"use strict";

let $nextLicenseJs = {
    init: function(){
        if( !nJs ){
            return;
        }

        let $form = document.querySelector('#nextactive-form');
		if( $form ){	
            $form.addEventListener('submit', $nextLicenseJs.activeLicense);
		}
        let $revoke = document.querySelector('.__revoke_license');
		if( $revoke ){	
            $revoke.addEventListener('click', $nextLicenseJs.revokeLicense);
		}

    },
    activeLicense: function( e ){
        e.preventDefault();
        let $this = this;
        
        let $contin = document.querySelector('.next3aws-section');
        if( $contin ){
            $contin.classList.add('ncode-disabled');
        }
        

        nJs.ajax(
			{
				'action' : window.nextactive.ajaxurl+'?action=nextactive_next3aws',
				'method' : 'POST', 
				'header' : {
				   'X-WP-Nonce' : window.nextactive.nonce
				},
				'data' : nJs.serialize($this)
			}
		).onload = function() {
			
			if (this.readyState == 4 && this.status == 200) {
				let $res = JSON.parse(this.responseText);

                let $mess = document.querySelector('.nextactive-message');
                if(!$mess){
                    return;
                }

                if($res.status){
                    $mess.classList.add('next-'+$res.status);
                    $mess.innerHTML = $res.message;
                    $contin.classList.remove('ncode-disabled');

                    if ($res.status == 'success') {
                        setTimeout( function(){ 
                            window.location.reload();
                        }  , 1500 );
                    }
                }
                
            }
        }
    },
    revokeLicense: function( e ){
        e.preventDefault();
        var $this = this;
        var $key = $this.getAttribute('data-keys');
        if(!$key){
            return;
        }
        let formdata = {
            keys: $key
        };
        nJs.ajax(
			{
				'action' : window.nextactive.ajaxurl+'?action=nextinactive_next3aws',
				'method' : 'GET', 
				'header' : {
				   'X-WP-Nonce' : window.nextactive.nonce
				},
				'data' : formdata
			}
		).onload = function() {
			
			if (this.readyState == 4 && this.status == 200) {
				let $res = JSON.parse(this.responseText);

                if($res.status){
                    setTimeout( function(){ 
                        window.location.reload();
                    }  , 1000 );
                }else{
                    $this.innerHTML = 'Sorry!! Do not Revoke License';
                }
            }
        }
    }
};

$nextLicenseJs.init();