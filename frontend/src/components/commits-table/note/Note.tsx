import * as React from 'react';

interface NoteProps {
  children?: React.ReactNode;
}

const Note: React.FunctionComponent<NoteProps> = ({ children }) => (
  <tbody>
    <tr className='note'>
      <td colSpan={6}>
        {children}
      </td>
    </tr>
  </tbody>
);

export default Note;
