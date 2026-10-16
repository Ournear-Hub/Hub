import Auth from '@/Pages/Account/Auth';

type LoginProps = {
    tab?: 'login' | 'signup';
    registeredEmail?: string | null;
};

export default function Login({ tab = 'login', registeredEmail = null }: LoginProps) {
    return <Auth tab={tab} registeredEmail={registeredEmail} />;
}
