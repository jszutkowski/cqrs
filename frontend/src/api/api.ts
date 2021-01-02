import axios, {AxiosError, AxiosPromise, AxiosRequestConfig, AxiosResponse} from "axios";
import IWallet from "../view/IWallet";
import ISimpleWallets from "../view/ISimpleWallets";
import ILoginResponse from "../security/ILoginResponse";
import IUserCredentials from "../security/IUserCredentials";
import TokenProvider from "../security/TokenProvider";
import { history } from "../history/history";

const securedArea = axios.create({
    baseURL: process.env.API_URL,
    timeout: 5000,
    headers: {'Content-Type': 'application/json'}
});

const notSecuredArea = axios.create({
    baseURL: process.env.API_URL,
    timeout: 5000,
    headers: {'Content-Type': 'application/json'}
});

securedArea.interceptors.request.use(
    (config: AxiosRequestConfig): AxiosRequestConfig => {
        config.headers.Authorization = `Bearer ${TokenProvider.getToken()}`;

        return config;
    },
    (error: AxiosError): Promise<never> => {
        return Promise.reject(error);
    }
);

securedArea.interceptors.response.use(
    (response: AxiosResponse): AxiosResponse => response,
    (error: AxiosError) => {
        if (error.response.status !== 401) {
            return Promise.reject(error);
        }

        return api
            .refreshToken()
            .then((response: AxiosResponse<ILoginResponse>): AxiosPromise => {
                TokenProvider.setToken(response.data.token);
                TokenProvider.setRefreshToken(response.data.refresh_token);

                error.response.config.headers['Authorization'] = 'Bearer ' + response.data.token;

                return securedArea(error.response.config);
            })
            .catch((error: AxiosError): Promise<never> => {
                TokenProvider.setToken(null);
                TokenProvider.setRefreshToken(null);

                history.push('/login')

                return Promise.reject(error)
            })
    }
)

const api = {
    login: (userCredentials: IUserCredentials): Promise<AxiosResponse<ILoginResponse>> => {
        return notSecuredArea.post(
            `/api/auth/login`, userCredentials
        )
    },
    refreshToken: (): Promise<AxiosResponse<ILoginResponse>> => {
        return notSecuredArea.post(
            `/api/auth/refresh-token`, { refresh_token: TokenProvider.getRefreshToken() }
        )
    },
    getWallets: (): Promise<AxiosResponse<ISimpleWallets>> => {
        return securedArea.get(`/api/wallet`)
    },
    getWallet: (walletId: string): Promise<AxiosResponse<IWallet>> => {
        return securedArea.get(`/api/wallet/${walletId}`)
    },
    createWallet: (): Promise<AxiosResponse<any>> => {
        return securedArea.post(`/api/wallet`)
    },
    addPoints: (walletId: string, points: number): Promise<AxiosResponse<any>> => {
        return securedArea.post(
            `/api/wallet/${walletId}`, {
                points: points
            }, {
                headers: {
                    'X-Command-Name': 'AddPoints',
                }
            }
        )
    }
}

export default api;
