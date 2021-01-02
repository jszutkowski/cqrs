import * as React from "react";
import {Button, Container, createStyles, Grid, makeStyles, TextField, Theme} from '@material-ui/core';
import {useForm} from 'react-hook-form';
import IUserCredentials from "../security/IUserCredentials";
import api from "../api/api";
import ILoginResponse from "../security/ILoginResponse";
// import { ErrorMessage } from '@hookform/error-message';
import {useHistory} from "react-router-dom";
import {AxiosError, AxiosResponse} from "axios";
import TokenProvider from "../security/TokenProvider";

const useStyles = makeStyles((theme: Theme) =>
    createStyles({
        container: {
            padding: theme.spacing(3),
            backgroundColor: '#fff'
        },
    }),
);

export default function Login() {
    const classes = useStyles();
    const history = useHistory();

    const { handleSubmit, register, errors } = useForm<IUserCredentials>();

    const onSubmit = handleSubmit((userCredentials: IUserCredentials) => {
        api
            .login(userCredentials)
            .then((response: AxiosResponse<ILoginResponse>): void => {
                TokenProvider.setToken(response.data.token);
                TokenProvider.setRefreshToken(response.data.refresh_token);

                history.push('/');
            })
            .catch((error: AxiosError) => {
                alert(error.response.data.message); //@todo: handle error response
            })
    });

    return (
        <Container className={classes.container} maxWidth="xs">
            <form onSubmit={onSubmit}>
                <Grid container spacing={3}>
                    <Grid item xs={12}>
                        <Grid container spacing={2}>
                            <Grid item xs={12}>
                                <TextField
                                    // inputRef={register({ required: true, minLength: {value: 20, message: 'blablabla'} })}
                                    inputRef={register}
                                    fullWidth
                                    label="Username"
                                    name="username"
                                    size="small"
                                    variant="outlined"
                                />
                                {/*<ErrorMessage errors={errors} name="singleErrorInput" />*/}
                            </Grid>
                            <Grid item xs={12}>
                                <TextField
                                    inputRef={register}
                                    fullWidth
                                    label="Password"
                                    name="password"
                                    size="small"
                                    type="password"
                                    variant="outlined"
                                />
                            </Grid>
                        </Grid>
                    </Grid>
                    <Grid item xs={12}>
                        <Button color="secondary" fullWidth type="submit" variant="contained">
                            Log in
                        </Button>
                    </Grid>
                </Grid>
            </form>
        </Container>
    );
}
