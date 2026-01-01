export default {
    methods: {
        setFormData(form, data){
            Object.keys(form).forEach((key)=>{
                if(data[key]!=undefined){
                    form[key]=data[key];
                }
            })
        },
        asset(url){
            return `${Ziggy.url}/${url}`
        },
        can(permissionName) {//PARA LOS PERMISOS
            if (!permissionName) return false;
            const permissions = this.session_permissions;
            if (!Array.isArray(permissions)) return false;
            return permissions.includes(permissionName);
        },
    },
    computed: {
        session_permissions() {
            const inertiaPermissions = this.$page?.props?.auth?.permissions;
            if (Array.isArray(inertiaPermissions)) {
                return inertiaPermissions
                    .map((p) => (typeof p === 'string' ? p : p?.permission))
                    .filter(Boolean);
            }

            const session = this.$store?.getters?.['getApiData'];
            const storePermissions = session?.permissions_user;
            if (Array.isArray(storePermissions)) return storePermissions;

            return [];
        },
    },
}
