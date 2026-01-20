define(['jquery', 'local_help/dataTables', 'core/modal_factory', 'core/templates', 'core/modal_events'], 
	   function($, d, ModalFactory, Templates, ModalEvents){
	return {
		init: function($holdingid, $sort, $dir, $page, $perpage) {
			$(document).ready(function(){

				var holdingid = $holdingid;
				var sort = $sort; 
				var dir = $dir; 
				var page = $page; 
				var perpage = $perpage;

				//Columnas
				var holding = M.util.get_string('holding', 'local_holdingmng');
				var user = M.util.get_string('user', 'local_holdingmng');
			    var deletestr = M.util.get_string('deleteuserstr', 'local_holdingmng');

			    //message
			    var nodata = M.util.get_string('nodata', 'local_holdingmng');
				
				//campos de usuario
				var columnstable = [
					{data: "holding", title: holding},
					{data: "user", title: user},
					{
						data: "deletestr",
						title: deletestr,
						render: function(data, type, row) {
							return `<a href="#" userid="${row.userid}" class="delete-user">${data}</a>`;
						}
					}
				];

				$('#holdingmng-table').DataTable({
					'order': [[0, 'asc']],
					// 'language': { 
					// 	'url': '//cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json'
					// },
					'searching': true, // Enable the search functionality
					'paging': true, // Enable pagination
					'pageLength': perpage, // Set the number of rows per page
					'columns': columnstable,
					ajax: {
						url: "/local/holdingmng/users_ajax.php",
						type: "POST",
						data: function(d) {
							d.holdingid = holdingid;
							d.sort = sort;
							d.dir = dir;
							d.size = perpage;
							d.page = page;
						},
						error: function(xhr, textStatus, errorThrown) {
							$("#holdingmng-table").html("");
							$(".centerbutton").hide();
							$("#holdingmng-table").css({"height": "auto"});
							$("#holdingmng-table").removeClass("dataTable");
							$("#holdingmng-table").html(`<div class='alert alert-info'>${nodata}</div>`);
						}
					},
					columns: columnstable,
					pageLength: perpage,
					lengthMenu: [10, 20, 50, 100],
					responsive: true,
					autoWidth: false
				});

				// Event listener for delete user
				$('#holdingmng-table').on('click', '.item-delete', function(e) {
					e.preventDefault();
					var userid = $(this).attr('userid');

					ModalFactory.create({
						type: ModalFactory.types.SAVE_CANCEL,
						title: M.util.get_string('confirmtitle', 'local_holdingmng'),
						body: M.util.get_string('confirmmessage', 'local_holdingmng'),
					})
					.then(function(modal) {
						modal.setSaveButtonText(M.util.get_string('remove', 'local_holdingmng'));
						var root = modal.getRoot();
						root.on(ModalEvents.save, function() {
							var parameter = {
								"userid": userid,
								"holdingid": holdingid
							};
							$.ajax({
								url: '/local/holdingmng/delusers.php',
								data: parameter,
								dataType: 'json',
								error: function() {
									// Handle error
								}
							}).done(function(message) {
								if (message.message === "ok") {
									var url = `/local/holdingmng/users.php?holdingid=${holdingid}&action=view&removeuser=ok`;
									window.location.href = url;
								}
							});
						});
						modal.show();
					});
				});
				
			});
		}
	};
});