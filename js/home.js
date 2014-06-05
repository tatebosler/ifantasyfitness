// A JavaScript file for various random things around iFF.

$("#toggle-daily-goals").click(function() {
	$("#daily-goal-data").toggle();
});

$(".populate").click(function () {
	$("#miles").val($(this).data("value"));
	$("#notes").val($(this).data("notes"));
});

$(".show-team-members").hover(function() {
	$($(this).data("target")).toggle();
})