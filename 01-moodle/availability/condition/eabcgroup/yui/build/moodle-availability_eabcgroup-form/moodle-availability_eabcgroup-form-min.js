YUI.add("moodle-availability_eabcgroup-form", function(e, t) {
    M.availability_eabcgroup = M.availability_eabcgroup || {}, M.availability_eabcgroup.form = e.Object(M.core_availability.plugin), M.availability_eabcgroup.form.eabcgroups = null, M.availability_eabcgroup.form.initInner = function(e) {
        this.eabcgroups = e
    }, M.availability_eabcgroup.form.getNode = function(t) {
        var n = '<label><span class="p-r-1">' + M.util.get_string("title", "availability_eabcgroup") + "</span> " + '<span class="availability-eabcgroup">' + '<select name="id" class="custom-select">' + '<option value="choose">' + M.util.get_string("choosedots", "moodle") + "</option>" + '<option value="active">' + M.util.get_string("activeeabcgroup", "availability_eabcgroup") + "</option>";
        n += "</select></span></label>";
        var s = e.Node.create('<span class="form-inline">' + n + "</span>");
        t.creating === undefined && (t.id !== undefined && s.one("select[name=id] > option[value=" + t.id + "]") ? s.one("select[name=id]").set("value", "" + t.id) : t.id === undefined && s.one("select[name=id]").set("value", "active"));
        if (!M.availability_eabcgroup.form.addedEvents) {
            M.availability_eabcgroup.form.addedEvents = !0;
            var o = e.one(".availability-field");
            o.delegate("change", function() {
                M.core_availability.form.update()
            }, ".availability_eabcgroup select")
        }
        return s
    }, M.availability_eabcgroup.form.fillValue = function(e, t) {
        var n = t.one("select[name=id]").get("value");
        n === "choose" ? e.id = "choose" : n !== "active" && (e.id = parseInt(n, 10))
    }, M.availability_eabcgroup.form.fillErrors = function(e, t) {
        var n = {};
        this.fillValue(n, t), n.id && n.id === "choose" && e.push("availability_eabcgroup:error_selecteabcgroup")
    }
}, "@VERSION@", {
    requires: ["base", "node", "event", "moodle-core_availability-form"]
});