function Metatemplate(){this.templateform=!1,this.selectQuery=function(e){return document.querySelector(this.templateform+" "+e)},this.selectQueryAll=function(e){return document.querySelectorAll(this.templateform+" "+e)},this.payload=function(e){let t=[];this.templateform=e;let n=this.selectQuery("");const a=this.selectQuery(".Language").value,s=this.selectQuery(".ImageUrl").value,l=Object.fromEntries(Array.from(n.elements).map((e=>{const t=e;return[t.name??t.id,t.value]})));return this.selectQueryAll(".Variables input").forEach((e=>{""!==e.value&&t.push(e.value)})),{type:"template",template_name:l.LoadedTemplate,language:a,variables:t,BodyTemplate:this.selectQuery(".BodyTemplate").value,image_url:s}},this.init=function(e){this.templateform=e;var t=this;const n=t.selectQuery(".RemVariableButton"),a=(this.selectQuery(".TemplateName"),this.selectQuery(".AddButton"),this.selectQuery(".LoadedTemplate")),s=(a.parentElement,this.selectQuery(".Variables .variables")),l=this.selectQuery(".AddVariableButton"),o=this.selectQuery(".BodyTemplate"),r=this.selectQuery(".Language"),i=this.selectQuery(".ImageUrl"),u=this.selectQuery(".Buttons"),c=this.selectQuery(".FooterTemplate"),p=document.createElement("img");p.style.maxWidth="33px",p.style.height="33px",p.style.objectFit="cover",p.style.borderRadius="8px 0px 0px 8px",p.style.border=".8px solid var(--chat-border-color)",p.style.padding="3px",i.insertAdjacentElement("beforebegin",p);let m={};a.addEventListener("change",(function(){const e=this.value,t=new XMLHttpRequest;t.onreadystatechange=function(){if(4===this.readyState&&200===this.status){const t=JSON.parse(this.responseText).data.find((t=>t.name===e));if(t){const e=t.components.find((e=>"BODY"===e.type)),n=e?e.text:"";o.value=n,r.value=t.language;const a=t.components.find((e=>"HEADER"===e.type&&"IMAGE"===e.format));a&&a.example&&a.example.header_handle?(p.src=a.example.header_handle[0],i.value=""):(p.src="",i.value=""),u.innerHTML="";t.components.filter((e=>"BUTTONS"===e.type&&e.buttons)).forEach((e=>{e.buttons&&e.buttons.forEach(((e,t)=>{const n=document.createElement("p");n.className=`api-dynamic-button Button${t}`,n.name=`Button${t}Text`,n.textContent=e.text,u.appendChild(n)}))}));const s=t.components.find((e=>"FOOTER"===e.type));c.textContent=s?s.text:""}}},t.open("GET","uploads/meta_templates.json",!0),t.send()})),o.addEventListener("blur",(function(e){const t=o.value;for(const e in m){const n=m[e];if(t===n.template)return a.value=e,r.value=n.language,void(c.textContent=n.footer)}})),l.addEventListener("click",(function(){const e=t.selectQueryAll(".variables input");if(e.length>=4)return;const n=document.createElement("input");n.setAttribute("style","margin: 2px 2px;");const a=e.length;n.value=`{{${a+1}}}`,s.appendChild(n)})),n.addEventListener("click",(function(){const e=t.selectQueryAll(".variables input"),n=e.length;n>1&&(e[e.length-1].remove(),n.value="{{1}}"),SBChat.showResponse("Variable Removed")})),function(){const e=new XMLHttpRequest;e.onreadystatechange=function(){if(4===this.readyState&&200===this.status){const e=JSON.parse(this.responseText).data;if(a.innerHTML="",e.forEach((e=>{const t=document.createElement("option");t.value=e.name,t.textContent=e.name,a.appendChild(t)})),e.length>0){const t=e[0].name;a.value=t,a.dispatchEvent(new Event("change"))}}},e.open("GET","uploads/meta_templates.json",!0),e.send()}()}}