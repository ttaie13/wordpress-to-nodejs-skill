import content from '../../content.json';
import {notFound} from 'next/navigation';
export function generateStaticParams(){return content.filter(p=>p.path).map(p=>({slug:p.path.split('/')}));}
export default async function Page({params}:{params:Promise<{slug:string[]}>}){
 const {slug}=await params;
 const page=content.find(p=>p.path===slug.join('/'));
 if(!page) notFound();
 // Only import trusted administrator-reviewed content; raw WordPress HTML can contain scripts.
 return <main style={{maxWidth:960,margin:'3rem auto',padding:24}}><h1>{page.title}</h1><article dangerouslySetInnerHTML={{__html:page.html}} /></main>;
}
