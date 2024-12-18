document.addEventListener("DOMContentLoaded", () => {
    fetch(textflareAjax.template_url)
        .then(response => response.text())
        .then(template => {
            new Vue({
                el: "#textflare-app",
                data() {
                    return {
                        configs: [],
                        newConfig: {
                            animationId: "zoom",
                            duration: 1000,
                            delay: 1000,
                            styleConfig: {
                                textColor: "#000000",
                                fontSize: "20",
                                fontFamily: "Arial, sans-serif",
                                backgroundColor: "#ffffff",
                                textAlign: "center",
                            },
                            textList: [],
                            newText: "",
                            height: 0,
                        },
                        editConfig: null,
                        animations: [
                            { id: "zoom", name: "Zoom In/Out" },
                            { id: "fade", name: "Fade" },
                            { id: "cube", name: "Cube" },
                        ],
                    };
                },
                methods: {
                    loadConfigs() {
                        fetch(textflareAjax.ajax_url + "?action=textflare_get_configs")
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    this.configs = data.data.configs.map(config => {
                                        config.animationId = config.animation_id;
                                        config.duration = config.duration;
                                        config.delay = config.delay;
                                        config.styleConfig = JSON.parse(config.style_config);
                                        config.textList = JSON.parse(config.text_list);
                                        return config;
                                    });
                                } else {
                                    alert("Error loading configurations.");
                                }
                            });
                    },
                    addText() {
                        if (this.newConfig.newText.trim() !== "") {
                            this.newConfig.textList.push(this.newConfig.newText.trim());
                            this.newConfig.newText = "";
                        }
                    },
                    removeText(index) {
                        this.newConfig.textList.splice(index, 1);
                    },
                    saveConfig() {
                        const configData = {
                            animationId: this.newConfig.animationId,
                            duration: this.newConfig.duration,
                            delay: this.newConfig.delay,
                            styleConfig: this.newConfig.styleConfig,
                            textList: this.newConfig.textList,
                            height: this.newConfig.height,
                        };

                        fetch(textflareAjax.ajax_url + "?action=textflare_save_config", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify(configData),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success) {
                                    alert("Configuration saved successfully!");
                                    this.loadConfigs();
                                    this.resetNewConfig();
                                } else {
                                    alert("Error saving configuration.");
                                }
                            });
                    },
                    resetNewConfig() {
                        this.newConfig = {
                            animationId: "zoom",
                            duration: 1000,
                            delay: 1000,
                            styleConfig: {
                                textColor: "#000000",
                                fontSize: "16px",
                                fontFamily: "Arial, sans-serif",
                                backgroundColor: "#ffffff",
                                textAlign: "center",
                            },
                            textList: [],
                            newText: "",
                            height: 0,
                        };
                        this.editConfig = null;
                    },
                    removeConfig(index) {
                        const configId = this.configs[index].id;
                        fetch(textflareAjax.ajax_url + "?action=textflare_delete_config", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ id: configId }),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success) {
                                    alert("Configuration deleted successfully!");
                                    this.configs.splice(index, 1);
                                } else {
                                    alert("Error deleting configuration.");
                                }
                            });
                    },
                    editExistingConfig(index) {
                        const config = this.configs[index];
                        this.newConfig = {
                            animationId: config.animationId,
                            duration: config.duration,
                            delay: config.delay,
                            styleConfig: config.styleConfig,
                            textList: config.textList,
                            newText: "",
                            height: config.height,
                        };
                        this.editConfig = config.id;
                    },
                    updateConfig() {
                        const configData = {
                            id: this.editConfig,
                            animationId: this.newConfig.animationId,
                            duration: this.newConfig.duration,
                            delay: this.newConfig.delay,
                            styleConfig: this.newConfig.styleConfig,
                            textList: this.newConfig.textList,
                            height: this.newConfig.height,
                        };

                        fetch(textflareAjax.ajax_url + "?action=textflare_update_config", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify(configData),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success) {
                                    alert("Configuration updated successfully!");
                                    this.loadConfigs();
                                    this.resetNewConfig();
                                } else {
                                    alert("Error updating configuration.");
                                }
                            });
                    },
                    copyShortcode(event) {
                        event.target.select();
                        document.execCommand('copy');
                        alert('Shortcode copied to clipboard!');
                    },
                },
                mounted() {
                    this.loadConfigs();
                },
                template: template,
            });
        });
});
