import * as React from 'react';
import { observer } from 'mobx-react';

interface ErrorProps {
  error: string;
}

const Error: React.FunctionComponent<ErrorProps> = ({ error }) => (
  <div className='CommitPanel-error'>
    <p>{error}</p>
  </div>
);

export default observer(Error);
