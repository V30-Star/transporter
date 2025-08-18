import "./bootstrap";
import Alpine from "alpinejs";

window.Alpine = Alpine;
document.addEventListener("alpine:init", () => {
    console.log("Alpine inited", Alpine.version);
});
Alpine.start();
