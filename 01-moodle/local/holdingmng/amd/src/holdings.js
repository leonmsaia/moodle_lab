define(['jquery', 'local_help/dataTables', 'core/modal_factory', 'core/modal_events'], 
	function($, d, ModalFactory, ModalEvents){
	return {
		init: function($holdingid, $sort, $dir, $page, $perpage) {
			$(document).ready(function(){

				var holdingid = $holdingid;
				var sort = $sort; 
				var dir = $dir; 
				var page = $page; 
				var perpage = $perpage;

				//Columnas
				var holdingidstr = M.util.get_string('holdingid', 'local_holdingmng');
				var holding = M.util.get_string('holding', 'local_holdingmng');
				var addusers = M.util.get_string('viewusers', 'local_holdingmng');
				var addcompanies = M.util.get_string('viewcompanies', 'local_holdingmng');
			    var edit = M.util.get_string('edit', 'local_holdingmng');
			    var deletestr = M.util.get_string('deletestr', 'local_holdingmng');
			   
			    //message
			    var nodata = M.util.get_string('nodata', 'local_holdingmng');

				$('#holdingmng-table').DataTable({
					'order': [[0, 'asc']],
					// 'language': { 
					// 	'url': '//cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json'
					// },
					'searching': true, // Enable the search functionality
					'paging': true, // Enable pagination
					'pageLength': perpage, // Set the number of rows per page
					'columns': [
						{ title: holdingidstr, data: "id" },
						{ title: holding, data: "name" },
						{ title: addusers, data: "addusers" },
						{ title: addcompanies, data: "addcompanies" },
						{ title: edit, data: "edit" },
						{ 
							title: deletestr, 
							data: "deletestr",
							render: function(data, type, row) {
								return `<a href="#" class="delete-link" holdingid="${row.id}">${data}</a>`;
							}
						}
					],
					'ajax': {
						'url': '/local/holdingmng/holding_ajax.php',
						'type': 'POST',
						'data': function(d) {
							// Add custom parameters to the AJAX request
							d.holdingid = holdingid;
							d.sort = sort;
							d.dir = dir;
							// d.size = perpage;
							d.page = page;
							return d;
						},
						'dataSrc': function(json) {
							return json.data || [];
						}
					}
				});

				// Add click event for delete links
				$('#holdingmng-table').on('click', '.item-delete', function(e) {
					var holdingid = $(this).attr('holdingid');
					console.log('holding id: ' + holdingid);
					

					ModalFactory.create({
						type: ModalFactory.types.SAVE_CANCEL,
						title: M.util.get_string('confirmtitleholding', 'local_holdingmng'),
						body: M.util.get_string('confirmmessageholding', 'local_holdingmng'),
					})
					.then(function(modal) {
						modal.setSaveButtonText('Delete');
						var root = modal.getRoot();
						root.on(ModalEvents.save, function() {
							var parameter = {
								"holdingid": holdingid
							};
							$.ajax({
								url: '/local/holdingmng/delholdings.php',
								data: parameter,
								dataType: 'json',
								error: function() {
									// Handle error
								}
							}).done(function(message) {
								if (message.message == "ok") {
									console.log(message.message);
									var url = '/local/holdingmng/holdings.php';
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