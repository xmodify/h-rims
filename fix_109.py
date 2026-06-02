filepath = 'resources/views/debtor/1102050102_109.blade.php'
lines = open(filepath, encoding='utf-8').readlines()

# Replace index 654 to 720 (inclusive) = lines 655-721
start_idx = 654  # line 655 (0-indexed)
end_idx = 720    # line 721 (0-indexed), inclusive

new_block = (
    "                    var html = '';\n"
    "                    \n"
    "                    if(data.length > 0) {\n"
    "                        data.forEach(function(row) {\n"
    "                            html += `\n"
    "                                <tr>\n"
    "                                    <td class=\"text-center\"><input type=\"checkbox\" name=\"checkbox[]\" value=\"${row.an}\"></td> \n"
    "                                    <td align=\"left\">${row.ward || ''}</td>\n"
    "                                    <td align=\"center\">${row.hn}</td>\n"
    "                                    <td align=\"center\">${row.an}</td>\n"
    "                                    <td align=\"left\">${row.ptname}</td>\n"
    "                                    <td align=\"center\">${row.age_y}</td>\n"
    "                                    <td align=\"left\" width=\"8%\">${row.pttype}</td>\n"
    "                                    <td align=\"right\" width=\"6%\">${DateThai(row.regdate)}</td>\n"
    "                                    <td align=\"right\" width=\"6%\">${DateThai(row.dchdate)}</td>\n"
    "                                    <td align=\"right\">${row.pdx || ''}</td>\n"
    "                                    <td align=\"right\">${parseFloat(row.adjrw || 0).toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4})}</td>\n"
    "                                    <td align=\"right\" width=\"5%\">${parseFloat(row.income).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>\n"
    "                                    <td align=\"right\">${parseFloat(row.rcpt_money).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>\n"
    "                                    <td align=\"right\">${parseFloat(row.other).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>\n"
    "                                    <td align=\"right\">${parseFloat(row.debtor).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>\n"
    "                                    <td align=\"left\">${row.other_list || ''}</td>\n"
    "                                    <td align=\"left\">${row.ipt_coll_status_type_name || ''}</td>\n"
    "                                </tr>\n"
    "                            `;\n"
    "                        });\n"
    "                    }\n"
    "                    \n"
    "                    $('#debtor_search_body').html(html);\n"
    "                    $('#badge-tab2').text(data.length);\n"
    "\n"
    "                    if ($.fn.DataTable.isDataTable('#debtor_search')) {\n"
    "                        $('#debtor_search').DataTable().destroy();\n"
    "                    }\n"
    "                    $('#debtor_search').DataTable({\n"
    "                        dom: '<\"row mb-3\"<\"col-md-6\"l><\"col-md-6 d-flex justify-content-end align-items-center gap-2\"fB>rt<\"row mt-3\"<\"col-md-6\"i><\"col-md-6\"p>>',\n"
    "                        buttons: [{ extend: 'excelHtml5', text: '<i class=\"bi bi-file-earmark-excel me-1\"></i> Excel', className: 'btn btn-success btn-sm', title: '1102050102.109-\u0e25\u0e39\u0e01\u0e2b\u0e19\u0e35\u0e49\u0e04\u0e48\u0e32\u0e23\u0e31\u0e01\u0e29\u0e32 \u0e40\u0e1a\u0e34\u0e01\u0e15\u0e49\u0e19\u0e2a\u0e31\u0e07\u0e01\u0e31\u0e14 IP \u0e23\u0e2d\u0e22\u0e37\u0e19\u0e22\u0e31\u0e19' }],\n"
    "                        language: { search: \"\u0e04\u0e49\u0e19\u0e2b\u0e32:\", lengthMenu: \"\u0e41\u0e2a\u0e14\u0e07 _MENU_ \u0e23\u0e32\u0e22\u0e01\u0e32\u0e23\", info: \"\u0e41\u0e2a\u0e14\u0e07 _START_ \u0e16\u0e36\u0e07 _END_ \u0e08\u0e32\u0e01\u0e17\u0e31\u0e49\u0e07\u0e2b\u0e21\u0e14 _TOTAL_ \u0e23\u0e32\u0e22\u0e01\u0e32\u0e23\", paginate: { previous: \"\u0e01\u0e48\u0e2d\u0e19\u0e2b\u0e19\u0e49\u0e32\", next: \"\u0e16\u0e31\u0e14\u0e44\u0e1b\" } }\n"
    "                    });\n"
    "                },\n"
    "                error: function() {\n"
    "                    tab2Loaded = false;\n"
    "                    $('#loading-tab2').addClass('d-none');\n"
    "                    $('#badge-tab2').html('!').css('color', 'red');\n"
    "                    $('#empty-tab2').removeClass('d-none');\n"
    "                    $('#table_109_ajax').addClass('d-none');\n"
    "                }\n"
    "            });\n"
    "        }\n"
)

new_lines = lines[:start_idx] + [new_block] + lines[end_idx+1:]
print(f'Original: {len(lines)} lines, New: {len(new_lines)} lines')

open(filepath, 'w', encoding='utf-8', newline='\n').writelines(new_lines)
print('Done!')
