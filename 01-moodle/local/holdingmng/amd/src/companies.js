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
				var company = M.util.get_string('company', 'local_holdingmng');
			    var deletestr = M.util.get_string('deletecompanystr', 'local_holdingmng');

			    //message
			    var nodata = M.util.get_string('nodata', 'local_holdingmng');
				
				//campos de usuario
				var columnstable = [
					{ title: holding, data: "holding" },
					{ title: company, data: "company" },
					{ 
						title: deletestr, 
						data: "deletestr",
						render: function(data, type, row) {
							return `<a href="#" class="delete-link" companyid="${row.companyid}" holdingid="${row.holdingid}">${data}</a>`;
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
					'ajax': {
						'url': '/local/holdingmng/companies_ajax.php',
						'type': 'POST',
						'data': function(d) {
							// Add custom parameters to the AJAX request
							d.holdingid = holdingid;
							d.sort = sort;
							d.dir = dir;
							d.size = perpage;
							d.page = page;
							return d;
						},
						'dataSrc': function(json) {
							// Assuming the server returns data in the format { data: [...] }
							return json.data || [];
						}
					}
				});

				// Add click event for delete links
				$('#holdingmng-table').on('click', '.delete-link', function(e) {
					e.preventDefault();
					var companyid = $(this).attr('companyid');
					var holdingid = $(this).attr('holdingid');

					ModalFactory.create({
						type: ModalFactory.types.SAVE_CANCEL,
						title: M.util.get_string('confirmcompanytitle', 'local_holdingmng'),
						body: M.util.get_string('confirmcompanymessage', 'local_holdingmng'),
					})
					.then(function(modal) {
						modal.setSaveButtonText(M.util.get_string('remove', 'local_holdingmng'));
						var root = modal.getRoot();
						root.on(ModalEvents.save, function() {
							var parameter = {
								"companyid": companyid,
								"holdingid": holdingid
							};
							$.ajax({
								url: '/local/holdingmng/delcompany.php',
								data: parameter,
								dataType: 'json',
								error: function() {
									console.error('Error deleting company');
								}
							}).done(function(message) {
								if (message.message === "ok") {
									console.log(message.message);
									var url = '/local/holdingmng/companies.php?holdingid=' + holdingid + '&action=view' + '&remove=ok';
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