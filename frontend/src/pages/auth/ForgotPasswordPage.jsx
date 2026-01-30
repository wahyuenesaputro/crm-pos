import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Coffee, Mail, ArrowRight, CheckCircle, AlertCircle } from 'lucide-react';
import api from '../../lib/api';

export default function ForgotPasswordPage() {
    const [email, setEmail] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isSent, setIsSent] = useState(false);
    const [error, setError] = useState('');
    const [devToken, setDevToken] = useState(null); // For development only
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        setError('');

        try {
            const res = await api.post('/auth/forgot-password', { email });
            setIsSent(true);

            // For development testing - capture token if returned
            if (res.data.data?.token) {
                setDevToken(res.data.data.token);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to send reset email');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
            <div className="sm:mx-auto sm:w-full sm:max-w-md">
                <div className="flex justify-center text-primary">
                    <Coffee className="w-12 h-12" />
                </div>
                <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Reset your password
                </h2>
                <p className="mt-2 text-center text-sm text-gray-600">
                    Enter your email address and we'll send you a link to reset your password.
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    {!isSent ? (
                        <form className="space-y-6" onSubmit={handleSubmit}>
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                    Email address
                                </label>
                                <div className="mt-1 relative rounded-md shadow-sm">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Mail className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        autoComplete="email"
                                        required
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        className="appearance-none block w-full pl-10 px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                                        placeholder="you@example.com"
                                    />
                                </div>
                            </div>

                            {error && (
                                <div className="rounded-md bg-red-50 p-4">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <AlertCircle className="h-5 w-5 text-red-400" />
                                        </div>
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-red-800">{error}</h3>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-70 disabled:cursor-not-allowed"
                                >
                                    {isSubmitting ? 'Sending...' : 'Send Reset Link'}
                                </button>
                            </div>

                            <div className="flex items-center justify-center">
                                <div className="text-sm">
                                    <Link to="/login" className="font-medium text-primary hover:text-primary/80">
                                        Back to Login
                                    </Link>
                                </div>
                            </div>
                        </form>
                    ) : (
                        <div className="text-center">
                            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                                <CheckCircle className="h-6 w-6 text-green-600" />
                            </div>
                            <h3 className="text-lg font-medium text-gray-900">Check your email</h3>
                            <p className="mt-2 text-sm text-gray-500">
                                We've sent a password reset link to <strong>{email}</strong>
                            </p>

                            {/* Development Helper */}
                            {devToken && (
                                <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md text-left overflow-hidden">
                                    <p className="text-xs font-bold text-yellow-800 uppercase mb-1">Dev Environment Only:</p>
                                    <p className="text-xs text-yellow-700 break-all mb-2">Token: {devToken}</p>
                                    <button
                                        onClick={() => navigate(`/reset-password?token=${devToken}&email=${email}`)}
                                        className="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded border border-yellow-300 hover:bg-yellow-200"
                                    >
                                        Use this token
                                    </button>
                                </div>
                            )}

                            <div className="mt-6">
                                <Link to="/login" className="text-sm font-medium text-primary hover:text-primary/80 flex items-center justify-center">
                                    <ArrowRight className="w-4 h-4 mr-1 rotate-180" />
                                    Back to Login
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
