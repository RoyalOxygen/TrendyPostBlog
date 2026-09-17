document.addEventListener('DOMContentLoaded',()=>{
const sidebar=document.getElementById('adminSidebar');
document.getElementById('topbarToggle')?.addEventListener('click',()=>sidebar.classList.toggle('open'));
document.getElementById('sidebarToggle')?.addEventListener('click',()=>sidebar.classList.remove('open'));
document.querySelectorAll('.delete-confirm').forEach(btn=>{btn.addEventListener('click',e=>{if(!confirm('Are you sure? This cannot be undone.'))e.preventDefault();})});
});