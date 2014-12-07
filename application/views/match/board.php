
<!DOCTYPE html>

<html>
	<head>
	<script src="http://code.jquery.com/jquery-latest.js"></script>
	<script src="<?= base_url() ?>/js/jquery.timers.js"></script>
	<script>

		var otherUser = "<?= $otherUser->login ?>";
		var user = "<?= $user->login ?>";
		var status = "<?= $status ?>";
		
		$(function(){
			$('body').everyTime(1000,function(){
					if (status == 'waiting') {
						$.getJSON('<?= base_url() ?>arcade/checkInvitation',function(data, text, jqZHR){
								if (data && data.status=='rejected') {
									alert("Sorry, your invitation to play was declined!");
									window.location.href = '<?= base_url() ?>arcade/index';
								}
								if (data && data.status=='accepted') {
									status = 'playing';
									$('#status').html('Playing ' + otherUser);
								}
								
						});
					}
					///////////
					if (status == 'playing') {
                        url = "<?= base_url() ?>board/getSlots";
                        $.getJSON(url, function(data, text, jqXHR) {
                            if (data && data.status == 'success') {
                                var boardArray = JSON.parse(data.blob);
                                if (boardArray[-1] != null) {
                                    currentUser = boardArray[-1];
                                }
                                for (var i = 0; i < data.size; i++) {
                                    var index = boardArray[i];
                                    if (index >= 42) {
                                        index = index - 42;
                                        fillSlot(index, data.yellow);
                                    }
                                    else {
                                        fillSlot(index, data.red);
                                    }
                                }
                                if (data.match_status == 'user1Won'){
                                    alert(data.user1Login + ' has won!');
                                    status = 'done';
                                    window.location = "<?= site_url()?>arcade/index/";
                                }
                                else if (data.match_status == 'user2Won') {
                                    alert(data.user2Login + ' has won!');
                                    status = 'done';
                                    window.location = "<?= site_url()?>arcade/index";
                                }
                                else if (data.match_status == 'tie') {
                                    alert('Tie game!');
                                    status = 'done';
                                    window.location = "<?= site_url()?>arcade/index";
                                }
                            }
                        });
                    }
                    ///////////
                var i, j;
                for (i = 0; i < 6; i++)
                {
                    for (j = 0; j < 7; j++)
                    {
                        var index = j + 7 * i;

                        $('#slot' + index).click({param1: j, param2: i}, function(event) {
                            $.post('<?= base_url() ?>board/postSlots', {
                                indexX: event.data.param1,
                                indexY: event.data.param2,
                                colNum: 7
                            }, function(data, textStatus, jqXHR) {

                            });
                        });
                    }
                }
                ////////////
					var url = "<?= base_url() ?>board/getMsg";
					$.getJSON(url, function (data,text,jqXHR){
						if (data && data.status=='success') {
							var conversation = $('[name=conversation]').val();
							var msg = data.message;
							if (msg.length > 0)
								$('[name=conversation]').val(conversation + "\n" + otherUser + ": " + msg);
						}
					});
			});

			

			$('form').submit(function(){
				var arguments = $(this).serialize();
				var url = "<?= base_url() ?>board/postMsg";
				$.post(url,arguments, function (data,textStatus,jqXHR){
						var conversation = $('[name=conversation]').val();
						var msg = $('[name=msg]').val();
						$('[name=conversation]').val(conversation + "\n" + user + ": " + msg);
						});
				return false;
				});	
		});
//////
	      function fillSlot(index, color)
            {

                if (status == 'playing')
                {
                    var slot = document.getElementById("slot" + index);
                    if (slot.src != color) {
                        slot.src = color;
                    }

                }

            }
	</script>
	</head> 
<body>  
	<h1>Game Area</h1>

	<div>
	Hello <?= $user->fullName() ?>  <?= anchor('account/logout','(Logout)') ?>  
	</div>
	
	<div id='status'> 
	<?php 
		if ($status == "playing")
			echo "Playing " . $otherUser->login;
		else
			echo "Wating on " . $otherUser->login;
	?>
	</div>
	
<?php 
	// game arena
	$rows = 6; // define number of rows
    $cols = 7; // define number of columns
    echo "<table>";
        for ($tr = 1; $tr <= $rows; $tr++) {
            echo "<tr>";
            for ($td = 1; $td <= $cols; $td++) {
                $index = ($td - 1) + $cols * ($tr - 1);
                echo "<td>";
                echo '<img type="button" id="slot' . $index . '" src="' . base_url("images/slot.png") . '"/>';
                echo "</td>";
            }
            echo "</tr>";
        }

        echo "</table>";

    // chat board
	echo form_textarea('conversation');
	
	echo form_open();
	echo form_input('msg');
	echo form_submit('Send','Send');
	echo form_close();
	
?>
	
	
	
	
</body>

</html>

