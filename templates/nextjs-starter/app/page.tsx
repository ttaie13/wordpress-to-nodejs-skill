import Link from 'next/link';
import content from '../content.json';
export default function Home() { return <main style={{maxWidth:960,margin:'3rem auto',padding:24}}><h1>WordPress migration staging</h1><p>Public content preview. Interactive WordPress features require separate migration.</p><ul>{content.map(p=><li key={p.path}><Link href={'/'+p.path}>{p.title || p.path || 'Home'}</Link></li>)}</ul></main> }
