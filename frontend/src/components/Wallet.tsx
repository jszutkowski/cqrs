import * as React from "react";
import {useEffect, useState} from "react";
import Container from '@material-ui/core/Container';
import {Button, createStyles, Grid, makeStyles, Paper, TextField, Theme} from "@material-ui/core";
import api from "../api/api";
import IWallet from "../view/IWallet";
import IPoints from "../view/IPoints";
import {io, Socket} from "socket.io-client";
import {Events} from "../sockets/Events";
import {useParams} from 'react-router-dom';
import IPointsAdded from "../sockets/events/IPointsAdded";

const useStyles = makeStyles((theme: Theme) =>
    createStyles({
        root: {
            flexGrow: 1,
            marginTop: 20
        },
        paper: {
            width: '100%',
        },
        control: {
            padding: theme.spacing(2),
        },
        pointsBox: {
            marginTop: '10px',
            padding: '0 10px'
        },
        addButtonContainer: {
            marginTop: 20,
        },
    }),
);

export default function Wallet() {
    const classes = useStyles();
    const {walletId} = useParams<{ walletId: string }>();

    const initialWallet: IWallet = {
        walletId: walletId,
        balance: 0,
        points: []
    };

    const [loading, setLoading] = useState(false);
    const [walletInitialized, setWalletInitialized] = useState(false);
    const [walletFetched, setWalletFetched] = useState(false);
    const [wallet, setWallet] = useState<IWallet>(initialWallet);
    const [socketInstance, setSocketInstance] = useState<Socket|null>(null);
    const [pointsInputValue, setPointsInputValue] = useState('');

    useEffect(() => {
        return () => {
            if (null !== socketInstance) {
                socketInstance.disconnect();
            }
        }
    }, [socketInstance]);

    const onPointsInputValueChanged = (event: any) => {
        setPointsInputValue(event.target.value)
    }

    const onAddPointsButtonClicked = () => {
        if (0 === +pointsInputValue) {
            return;
        }

        setLoading(true);

        api
            .addPoints(walletId, +pointsInputValue)
            .finally(() => setLoading(false))
    }

    const fetchWallet = () => {
        if (true === walletInitialized) {
            return;
        }

        setWalletInitialized(true);

        api
            .getWallet(walletId)
            .then(response => {
                setWallet(response.data);
            })
            .finally(() => {
                setLoading(false);
                setWalletFetched(true);
            })
    }

    const initializeSockets = () => {
        if (false === walletFetched || null !== socketInstance) {
            return;
        }

        const socket = io(process.env.SOCKETS_URL);

        setSocketInstance(socket);

        socket.on(Events.CONNECTED, () => {
            console.log('Connected to sockets');
        });

        socket.emit(Events.SUBSCRIBE_WALLET, walletId)

        socket.on(Events.POINTS_ADDED, (event: IPointsAdded) => {
            console.log(`On wallet event occurred: ${Events.POINTS_ADDED}`);
            addPoints(event);
        });
    }

    const addPoints = (event: IPointsAdded) => {
        const addedPoints: IPoints = {
            amount: event.amount,
            createdAt: event.createdAt
        }

        setWallet((prevWallet: IWallet) => {
            return {
                ...prevWallet,
                points: [
                    addedPoints,
                    ...prevWallet.points
                ]
            };
        });
    }

    fetchWallet();
    initializeSockets();

    return <Container maxWidth="sm">
        <Grid container className={classes.root} spacing={2}>
            <Grid item xs={12}>
                <Grid container justify="center" spacing={2}>
                    <Paper>
                        <TextField
                            type="number"
                            id="outlined-basic"
                            label="Loyalty points"
                            variant="outlined"
                            disabled={loading}
                            value={pointsInputValue}
                            onChange={onPointsInputValueChanged}
                        />
                    </Paper>
                </Grid>
                <Grid container justify="center" spacing={2} className={classes.addButtonContainer}>
                    <Paper>
                        <Button variant="contained" color="primary" disabled={loading}
                                onClick={onAddPointsButtonClicked}>
                            Add Points
                        </Button>
                    </Paper>
                </Grid>
            </Grid>
            <Grid item xs={12}>
                {wallet !== null && wallet.points.map((points: IPoints, key: number) =>
                    <Paper key={key} className={classes.pointsBox} elevation={3} variant="outlined">
                        <p>Points: {points.amount}</p>
                        <p>Added at: {points.createdAt}</p>
                    </Paper>
                )}
            </Grid>
        </Grid>
    </Container>
}
